# Upgrade Guide

## Upgrading to 4.x

This guide covers upgrading `binarybuilds/laritor-client` to 4.x.

### Upgrade the package

Update your Composer constraint, then refresh the lock file:

```sh
composer require binarybuilds/laritor-client:^4.0 --update-with-all-dependencies
```

### Optional: Update custom event filters

> This step is required only if your application uses a custom Laritor override filter class. If you are unsure how to upgrade your custom override class, rename your current override class, publish the new override class by following https://laritor.com/docs/customization and make any necessary changes after publishing.

4.x moves event filtering from the point where an event is recorded to just before the event batch is sent. Filters can therefore use final status and duration values. It also replaces the recording and payload environment variables from `config/laritor.php` with methods on an override class. Keep filters side-effect free, since they run while Laritor prepares a batch for delivery.

Configure 4.x filters in a class extending `BinaryBuilds\LaritorClient\Override\DefaultOverride`. The override receives the request, response, status, duration, user, and other completed-event data needed to make context-aware decisions.

| Filter or setting | 3.x signature / environment variable | 4.x override method |
| --- | --- | --- |
| Outbound request filter | `recordOutboundRequest($url)` | `recordOutboundRequest($url, $statusCode, $duration)` |
| Query filter | `recordQuery($query, $duration)` | `recordQuery($query, $duration, $path)` |
| Queued-job filter | `recordQueuedJob($job)` | `recordQueuedJob(string $connection, string $queue, string $job, string $status, $duration)` |
| Request filter | `recordRequest($request)` | `recordRequest($request, $response, $status, $duration, $user)` |
| Command / scheduled-task filter | `recordCommandOrScheduledTask($command)` | `recordCommandOrScheduledTask(string $command, string $status, $duration)` |
| Mail filter | `recordMail($message)` | `recordMail($mailable, $to, $subject)` |
| Log filter | _Not available_ | `recordLog($level, $message, array $context = [])` |
| Log level | `LARITOR_LOG_LEVEL` | `recordLog($level, $message, array $context = [])` |
| Context | `LARITOR_RECORD_CONTEXT` | `recordRequestContext()`, `recordCommandContext()`, `recordScheduledTaskContext()`, `recordQueuedJobContext()`, `recordLogContext()` |
| Database schema | `LARITOR_RECORD_DB_SCHEMA` | `recordDatabaseSchema()` |
| Query bindings | `LARITOR_RECORD_QUERY_BINDINGS` | `recordQueryBindings($query, $duration, $path)` |
| Request query string | `LARITOR_RECORD_QUERY_STRING` | `recordRequestQueryParameters()` |
| Request headers / body | `LARITOR_RECORD_REQUEST_HEADERS` / `LARITOR_RECORD_REQUEST_BODY` | `recordRequestHeaders()` / `recordRequestBody()` |
| Response headers / body | `LARITOR_RECORD_REQUEST_RESPONSE_HEADERS` / `LARITOR_RECORD_REQUEST_RESPONSE_BODY` | `recordResponseHeaders()` / `recordResponseBody()` |
| Session data | `LARITOR_RECORD_SESSION_DATA` | `recordSessionData()` |
| Outbound-request headers / body | `LARITOR_RECORD_OUTBOUND_REQUEST_HEADERS` / `LARITOR_RECORD_OUTBOUND_REQUEST_BODY` | `recordOutboundRequestHeaders()` / `recordOutboundRequestBody()` |
| Outbound response headers / body | `LARITOR_RECORD_OUTBOUND_REQUEST_RESPONSE_HEADERS` / `LARITOR_RECORD_OUTBOUND_REQUEST_RESPONSE_BODY` | `recordOutboundRequestResponseHeaders()` / `recordOutboundRequestResponseBody()` |
| Whitelisted vendors | `LARITOR_WHITELISTED_VENDORS` | `whitelistedVendors(): array` |

If your application implements `LaritorOverride` directly, implement every new payload/context method in the table as well as `recordLog()`. Extending `DefaultOverride` is the recommended migration path: only update the methods you need.

For example, this override retains the 3.x-style “only errors and above” log policy and disables request headers and session data:

```php
namespace App\Laritor;

use BinaryBuilds\LaritorClient\Override\DefaultOverride;

class LaritorDataFilter extends DefaultOverride
{
    public function recordLog($level, $message, array $context = []): bool
    {
        return in_array(strtolower($level), ['error', 'critical', 'alert', 'emergency'], true);
    }

    public function recordRequestHeaders($request, $response, $status, $duration, $user): bool
    {
        return false;
    }

    public function recordSessionData($request, $response, $status, $duration, $user): bool
    {
        return false;
    }
}
```

Bind the override in an application service provider (typically in `register`):

```php
use App\Laritor\LaritorDataFilter;
use BinaryBuilds\LaritorClient\Override\LaritorOverride;

$this->app->bind(LaritorOverride::class, LaritorDataFilter::class);
```

Review the defaults before deploying. `DefaultOverride` records request/response headers and session data by default; request and response bodies remain disabled. Existing redaction still applies, but applications with stricter data-collection requirements should explicitly return `false` from the relevant methods.

For example, a request filter can exclude successful health checks while retaining failures:

```php
public function recordRequest($request, $response, $status, $duration, $user): bool
{
    return ! $request->is('health') || $status >= 400;
}
```

### Use a generated filter preset (optional)

The filter generator now accepts an optional preset and creates `App\Laritor\LaritorDataFilter`:

```sh
# Full observability (default)
php artisan make:laritor-filter

# Capture data associated with failures and slow operations
php artisan make:laritor-filter issues-only

# Capture only exception-related data
php artisan make:laritor-filter exceptions-only
```

Bind the generated class as shown above. If a file with that name already exists, review and merge its customizations rather than overwriting it.

### Other behavior changes

- The default for `LARITOR_INGEST_EVENTS_WITHOUT_OCCURRENCE` is now `true`. Set it explicitly to `false` if you need the former default behavior.
- Cache events now include the cache store name and a `duration` field.
- The default filters omit Laritor's own cache keys, Laritor HTTP ingestion requests and routes, `QueueHealthCheck` jobs, and Laritor/internal Artisan commands, in addition to common framework and monitoring noise.
