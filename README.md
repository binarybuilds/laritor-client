> [!WARNING]
> This branch is only meant to be used with laravel 8.x. Please use the latest stable branch for all other versions.

Laritor Client Package
------------------------------------------------
![Laritor Image](/art/laritor-og.png "Laritor Image")

[Laritor](https://laritor.com) is a full-stack observability platform built for Laravel.

It gives you everything you need to understand what’s happening inside your application. From slow requests and 
database queries to failed jobs, exceptions, and server resource usage.

### ❤️ Why Developers Love Laritor

- **Find performance bottlenecks in seconds:** See exactly which queries, cache calls, or external requests are slowing your app.
- **Debug errors with full context:**. Every exception is captured with a complete request timeline, logs, database queries, and related events.
- **Monitor servers like a pro:** Track CPU, memory, and disk usage alongside your Laravel metrics — no extra server monitoring tool needed.
- **Stay ahead of production issues:** Get instant alerts for slow requests, failed jobs, unhealthy servers, or custom health checks.
- **Reduce time-to-fix dramatically:** No more guessing — pinpoint the problem and deploy a fix faster.

### 📄 Documentation
Full setup instructions, customization options, and API details are available at:

👉 [Laritor's Documentation](https://laritor.com/docs)

### 🚀 QuickStart

##### Step 1: Install the Package
```
composer require binarybuilds/laritor-client
```
##### Step 2: Configure Environment Variables
Add the following variables to your .env file.
```
LARITOR_ENABLED=true
LARITOR_INGEST_ENDPOINT=your-ingest-endpoint
LARITOR_BACKEND_KEY=laritor-key
```
##### Step 3: Sync After Each Deployment

Run this command after every deployment to sync scheduled tasks, database schema changes,
custom health checks, and server updates with Laritor.
Add it to the end of your deployment scripts.
```
php artisan laritor:sync
```
##### Step 4: (Optional) Collect Server Metrics
To collect server metrics such as CPU, memory, and disk usage, schedule this command to run every minute.
```
php artisan laritor:send-metrics
```

### ⚙️ Customization
You can customize and control the data shared with Laritor.
See the [customization guide](https://laritor.com/docs/customization) for details.

## 🔐 Security Vulnerabilities

If you discover a security vulnerability in this package, do not use the public issue tracker or disclose it publicly.
Please refer to our [Security Policy](https://github.com/binarybuilds/laritor-client/security/policy).

## 💬 Support
📧 Email: [support@laritor.com](mailto:support@laritor.com)

💬 Join: [Laritor Discord](https://discord.laritor.com)

## 📜 License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).