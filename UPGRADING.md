# Upgrading
## Upgrading to 4.x from 3.x

This guide covers upgrading `binarybuilds/laritor-client` from 3.x to 4.x.

### Upgrade the package

Update your Composer constraint, then refresh the lock file:

```sh
composer require binarybuilds/laritor-client:^4.0 --update-with-all-dependencies
```

### Update custom filters

4.x moves event filtering from the point where an event is recorded to just before the event batch is sent. This lets filters use the completed event data, such as an HTTP response status, request duration, queued-job outcome, or mail recipient.

If your application binds a custom implementation of `BinaryBuilds\LaritorClient\Override\LaritorOverride`, update it to match the new interface. A custom class that extends `DefaultOverride` only needs to update the methods it overrides; a class that implements the interface directly must implement the new `recordLog()` method as well.

| Filter | 3.x signature | 4.x signature |
| --- | --- | --- |
| Outbound request | `recordOutboundRequest($url)` | `recordOutboundRequest($url, $statusCode, $duration)` |
| Query | `recordQuery($query, $duration)` | `recordQuery($query, $duration, $path)` |
| Queued job | `recordQueuedJob($job)` | `recordQueuedJob(string $connection, string $queue, string $job, string $status, int $duration)` |
| Request | `recordRequest($request)` | `recordRequest($request, $response, $status, $duration, $user)` |
| Command / scheduled task | `recordCommandOrScheduledTask($command)` | `recordCommandOrScheduledTask(string $command, string $status, int $duration)` |
| Mail | `recordMail($message)` | `recordMail($mailable, $to, $subject)` |
| Log | _not available_ | `recordLog($level, $message, array $context = [])` |

### Review filtering behavior

`LARITOR_LOG_LEVEL` is no longer applied by `LogRecorder`. If you used it to limit logs, move that policy into a custom `recordLog()` filter, for example:

```php
public function recordLog($level, $message, array $context = []): bool
{
    return in_array(strtolower($level), ['error', 'critical', 'alert', 'emergency'], true);
}
```