Laritor Client Package
------------------------------------------------
![Laritor Image](/art/laritor-og.png "Laritor Image")

[Laritor](https://laritor.com) is a full-stack observability platform for Laravel applications. 

This is a client package for sending application telemetry to Laritor.

## Requirements
- PHP: `^7.4 | ^8.0 | ^8.1 | ^8.2 | ^8.3 | ^8.4 | ^8.5`
- Laravel: `^9 | ^10 | ^11 | ^12 | ^13`

## Installation
```
composer require binarybuilds/laritor-client
```

## Configuration
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

## Usage
Sync after each deployment to push scheduled tasks, schema changes, and health checks.
```
php artisan laritor:sync
```

Optional: collect server metrics (CPU, memory, disk) by scheduling this every minute.
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
Email: [support@laritor.com](mailto:support@laritor.com)
Discord: [Laritor Discord](https://discord.laritor.com)

## License
This package is open-sourced software licensed under the [MIT license](LICENSE.md).
