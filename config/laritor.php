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

    'serverless' => env('LARITOR_SERVERLESS', false),

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
     * If you are concerned about the cost, You can enable rate limiting for requests.
     * Rate limiting is enforced by request url per minute. Please remember, Enabling
     * rate limiting will result in certain issues go unnoticed.
     */
    'use_rate_limiter' => env('LARITOR_USE_RATE_LIMITER', false),

    /**
     * Here you can set the maximum number of requests per url per minute
     * that can be sent to laritor. For example, If you set a value of 5,
     * It will only send 5 requests per minute per url to laritor. Any
     * additional requests will be ignored until the next minute. This
     * will only work if use_rate_limiter is set to true.
     */
    'rate_limiter_attempts' => env('LARITOR_RATE_LIMITER_ATTEMPTS', 5),

    'query' => [
        /**
         * Set the below value to false if you do not want to record read queries.
         */
        'read' => env('LARITOR_RECORD_READ_QUERIES', true),

        /**
         * Set the below value to false if you do not want to record write queries.
         */
        'write' => env('LARITOR_RECORD_WRITE_QUERIES', true),

        /**
         * Set the below value to false if you do not want to record queries
         * executed in the console (jobs, commands, scheduled tasks).
         */
        'console' => env('LARITOR_RECORD_CONSOLE_QUERIES', true),

        /**
         * By default, Laritor will not record the bindings of the query.
         * This is to avoid sensitive data from being recorded. If you
         * wish to record the bindings, set the below value to true.
         */
        'bindings' => env('LARITOR_RECORD_QUERY_BINDINGS', false),
    ],

    'requests' => [

        /**
         * Any requests matching the below pattern will be ignored.
         */
        'ignore' => [
            'telescope/*'.
            '_debugbar*',
            '__clockwork*',
            '_ignition/*',
            'laritor/*'
        ]
    ],

    'outbound_requests' => [
        /**
         * Set the below value to false if you do not want to record outbound requests
         * executed in the console (jobs, commands, scheduled tasks).
         */
        'ignore_console_requests' => env('LARITOR_RECORD_CONSOLE_OUTBOUND_REQUESTS', true),

        /**
         * Any outbound urls matching the below pattern will be ignored.
         */
        'ignore' => [
        ]
    ],

    'exceptions' => [

        /**
         * The below exceptions will be ignored from sending to laritor.
         */
        'ignore' => [
            \Illuminate\Database\Eloquent\ModelNotFoundException::class,
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
            \Illuminate\Auth\Access\AuthorizationException::class,
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Validation\ValidationException::class,
        ]
    ],

    'jobs' => [

        /**
         * The below jobs will be ignored from sending to laritor.
         */
        'ignore' => [
            \Laritor\LaravelClient\Jobs\QueueHealthCheck::class
        ]
    ],
];