Laritor Client
------------------------------------------------
[Laritor](https://laritor.com) is a performance monitoring and observability tool for laravel apps. 

This repository contains the official package for laritor which collects and sends metrics from your application to laritor.

### QuickStart

##### Step 1: Install Package
```
composer require binarybuilds/laritor-client
```
##### Step 2: Publish Config
```
php artisan vendor:publish --provider="BinaryBuilds\LaritorClient\LaritorServiceProvider"
```
##### Step 3: Update Env Variables
```
LARITOR_ENABLED=true
LARITOR_INGEST_URL=your-ingest-url
```
##### Step 4: Run Laritor sync command after each deployment.

Run the below command after each deployment; so important code changes like scheduled tasks, database schema, 
custom health checks and server changes are synchronized with Laritor.
```
php artisan laritor:sync
```
##### Step 5: (Optional) Collect Server Metrics

If you wish to collect server metrics like cpu, memory and disk usage, Schedule the below command to run every minute.
```
php artisan laritor:send-metrics
```

### Customization

You can customize and restrict what information is shared with Laritor. For details check out
our [official documentation](https://docs.laritor.com).

## Security Vulnerabilities

If you found a security vulnerability with in this package, Please do not use the issue tracker. Instead, 
email  `support@laritor.com`. All security vulnerabilities will be addressed promptly.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).
