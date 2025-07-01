Official Laritor Client Package
------------------------------------------------
![Laritor Image](/art/laritor-og.png "Laritor Image")

[Laritor](https://laritor.com) is a performance monitoring and observability tool for applications built using laravel php framework.

This repository contains the ingest package for laritor which collects and sends metrics from your application to laritor.

##### [Laritor's Documentation](https://laritor.com/docs/)

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
LARITOR_INGEST_ENDPOINT=your-ingest-endpoint
LARITOR_BACKEND_KEY=laritor-key
```
##### Step 4: Run Laritor's sync command after each deployment.

Run the below command after each deployment to sync scheduled tasks, database schema,
custom health checks and server changes with Laritor.
```
php artisan laritor:sync
```
##### Step 5: (Optional) Collect Server Metrics

If you wish to collect server metrics like cpu, memory and disk usage, Schedule the below command to run every minute.
```
php artisan laritor:send-metrics
```

### Customization

You can customize and restrict what information is shared with Laritor. For details, check
our [customization guide](https://laritor.com/docs/customization/).

## Security Vulnerabilities

If you find a security vulnerability with in this package, Please do not use the issue tracker. Instead,
email  `support@laritor.com`. All security vulnerabilities will be addressed promptly.

## Support

For Laritor's support, email `support@laritor.com` or join our [discord](https://discord.laritor.com/).

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).