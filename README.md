# Performance monitoring and Observability for Laravel

[![Latest Version](https://img.shields.io/packagist/v/binarybuilds/laritor-client)](https://packagist.org/packages/binarybuilds/laritor-client)
[![Total Downloads](https://img.shields.io/packagist/dt/binarybuilds/laritor-client)](https://packagist.org/packages/binarybuilds/laritor-client)
[![Laravel Compatibility](https://badge.laravel.cloud/badge/binarybuilds/laritor-client)](https://packagist.org/packages/binarybuilds/laritor-client)
[![PHP Version](https://img.shields.io/packagist/php-v/binarybuilds/laritor-client)](https://packagist.org/packages/binarybuilds/laritor-client)
[![Tests](https://img.shields.io/github/actions/workflow/status/binarybuilds/laritor-client/ci.yml?label=tests)](https://github.com/binarybuilds/laritor-client/actions)
[![License](https://img.shields.io/github/license/binarybuilds/laritor-client)](https://github.com/binarybuilds/laritor-client/blob/main/LICENSE.md)
[![Agentless Setup](https://img.shields.io/badge/Setup-Agentless-brightgreen)](https://laritor.com)

[Laritor](https://laritor.com) is a Laravel-native observability and performance monitoring tool that captures requests, queries, jobs, and external calls into a single correlated timeline.

With built-in dashboards, alerts, and AI-powered insights, it helps you understand and optimize your application using real production data.

This package sends telemetry events from your Laravel application to Laritor.


------------------------------------------------
![Laritor Image](/art/laritor-og.png "Laritor Image")

---
## Why Laritor?

- See exactly what’s slowing your application
- Debug issues with full request timelines
- Detect N+1 queries and slow database calls
- Monitor queues, scheduled tasks, and failures
- Get real-time alerts for production issues
- No agents. No infrastructure changes

## Requirements
- PHP: `^8.0 | ^8.1 | ^8.2 | ^8.3 | ^8.4 | ^8.5`
- Laravel: `^9 | ^10 | ^11 | ^12 | ^13`

## Getting Started

### 1. Signup for Laritor
Signup for Laritor at https://laritor.com/signup

## 2. Install package
```
composer require binarybuilds/laritor-client
```

## 3. Add env variables
Add the following to your `.env`:
```
LARITOR_ENABLED=true
LARITOR_INGEST_ENDPOINT=your-ingest-endpoint
LARITOR_BACKEND_KEY=laritor-key
```

Environment variables:
- `LARITOR_ENABLED`: Enable/disable the client.
- `LARITOR_INGEST_ENDPOINT`: Laritor ingest URL for your account.
- `LARITOR_BACKEND_KEY`: Backend key for authentication.

## 4. Run command during deployment

After deployment, run:
```
php artisan laritor:sync
```
This syncs:
- Scheduled tasks
- Database schema
- Health checks

## 5. (Optional) Server Metrics
To collect CPU, memory, and disk usage, schedule below command to run every minute.
```
php artisan laritor:send-metrics
```

## Documentation
Setup details, configuration options, and API references:
- [Laritor Documentation](https://laritor.com/docs)

## Development
Run tests:
```
composer test
```

Static analysis:
```
vendor/bin/phpstan
```

## Security
If you discover a security vulnerability, do not use the public issue tracker or disclose it publicly.
Please refer to our [Security Policy](https://github.com/binarybuilds/laritor-client/security/policy).

## Support
- Email: [support@laritor.com](mailto:support@laritor.com)
- Discord: [Laritor Discord](https://discord.laritor.com)

## License
This package is open-sourced software licensed under the [MIT license](LICENSE.md).
