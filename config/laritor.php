<?php

return [

    /**
     * You can easily enable/disable Laritor by setting the below config value
     * to true or false.
     */
    'enabled' => env('LARITOR_ENABLED', true),

    /**
     * This is your laritor ingest url. You can find it in your laritor dashboard.
     */
    'ingest_url' => env('LARITOR_INGEST_URL'),

    /**
     * Here you can enable or disable a specific recorder. If you do not wish a specific recorder to
     * send events to laritor, You can comment it out below. Only recorders which are defined below
     * will send events to laritor.
     */
    'recorders' => [
        \Laritor\LaravelClient\Recorders\CacheRecorder::class,
        \Laritor\LaravelClient\Recorders\ExceptionRecorder::class,
        \Laritor\LaravelClient\Recorders\OutboundRequestRecorder::class,
        \Laritor\LaravelClient\Recorders\QueryRecorder::class,
        \Laritor\LaravelClient\Recorders\QueuedJobRecorder::class,
        \Laritor\LaravelClient\Recorders\RequestRecorder::class,
        \Laritor\LaravelClient\Recorders\CommandRecorder::class,
        \Laritor\LaravelClient\Recorders\ScheduledTaskRecorder::class,
        \Laritor\LaravelClient\Recorders\SchedulerRecorder::class,
        \Laritor\LaravelClient\Recorders\LogRecorder::class,
        \Laritor\LaravelClient\Recorders\MailRecorder::class,
        \Laritor\LaravelClient\Recorders\NotificationRecorder::class,
        \Laritor\LaravelClient\Recorders\DatabaseSchemaChangesRecorder::class,
    ],

    /**
     * For high traffic sites, some requests which has issues(slow running, slow queries, exceptions, etc) will
     * constantly hit laritor with lots of events. As the number of events in laritor are metered, It is better
     * to rate limit the number of events you send to laritor. If rate limiter is enabled, All requests are
     * rate limited to a given number per minute per request.
     */
    'use_rate_limiter' => env('LARITOR_USE_RATE_LIMITER', true),

    /**
     * Here you can define how many times can a request send events to laritor before hitting the rate limit. For example,
     * if you define a value of 5 below, Any requests to a specific url will send events to laritor upto 5 times every
     * minute.
     * Note: Rate limiting is only by request url. Means events sent from url a will not be counted towards rate limit
     * for events sent from url b. Each url has its own rate limits.
     */
    'rate_limiter_attempts' => env('LARITOR_RATE_LIMITER_ATTEMPTS', 5),

    'query' => [
        /**
         * If for any reason, you do not wish to send read queries(SELECT) to laritor, set the below value to false.
         * This will not report any read queries to laritor.
         */
        'read' => env('LARITOR_RECORD_READ_QUERIES', true),

        /**
         * Most of the applications primarily focus on read query performance. If you wish to also monitor the performance
         * of write (INSERT, UPDATE, DELETE) queries, set the below value to true.
         */
        'write' => env('LARITOR_RECORD_WRITE_QUERIES', true),

        /**
         * Most of the time, queries running in console do not cause any performance issues to the application. If you
         * wish to monitor the queries executed in the console as well, set the below value to true.
         */
        'console' => env('LARITOR_RECORD_CONSOLE_QUERIES', true),

        /**
         * To protect sensitive information, We only record SQL queries but not their bindings or data. If you wish to
         * send the bindings along with the query, Set the below value to true.
         */
        'bindings' => env('LARITOR_RECORD_QUERY_BINDINGS', false),
    ],

    'requests' => [
        'ignore' => [
            'telescope/*'.
            '_debugbar*',
            '__clockwork*',
            '_ignition/*',
            'laritor/*'
        ]
    ],

    'outbound_requests' => [
        'ignore_console_requests' => env('LARITOR_RECORD_CONSOLE_OUTBOUND_REQUESTS', true),
        'ignore' => [
        ]
    ],

    'exceptions' => [
        'ignore' => [
            \Illuminate\Database\Eloquent\ModelNotFoundException::class,
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
            \Illuminate\Auth\Access\AuthorizationException::class,
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Validation\ValidationException::class,
        ]
    ],

    'jobs' => [
        'ignore' => [
            \Laritor\LaravelClient\Jobs\QueueHealthCheck::class
        ]
    ],
];