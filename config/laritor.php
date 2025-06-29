<?php

return [

    'enabled' => env('LARITOR_ENABLED', true),

    'env' => env('LARITOR_ENV'),

    'ingest_endpoint' => env('LARITOR_INGEST_ENDPOINT'),

    'keys' => [
        'backend' => env('LARITOR_BACKEND_KEY')
    ],

    'server_name' => env('LARITOR_SERVER_NAME'),

    /**
     * If your application is running on a serverless environment,
     * Set the below value to true
     */
    'serverless' => env('LARITOR_SERVERLESS', false),

    /**
     * If you do not wish a specific recorder to send events to laritor,
     * You can comment it out below.
     */
    'recorders' => [
        \BinaryBuilds\LaritorClient\Recorders\CacheRecorder::class,
        \BinaryBuilds\LaritorClient\Recorders\ExceptionRecorder::class,
        \BinaryBuilds\LaritorClient\Recorders\OutboundRequestRecorder::class,
        \BinaryBuilds\LaritorClient\Recorders\QueryRecorder::class,
        \BinaryBuilds\LaritorClient\Recorders\QueuedJobRecorder::class,
        \BinaryBuilds\LaritorClient\Recorders\RequestRecorder::class,
        \BinaryBuilds\LaritorClient\Recorders\CommandRecorder::class,
        \BinaryBuilds\LaritorClient\Recorders\ScheduledTaskRecorder::class,
        \BinaryBuilds\LaritorClient\Recorders\SchedulerRecorder::class,
        \BinaryBuilds\LaritorClient\Recorders\LogRecorder::class,
        \BinaryBuilds\LaritorClient\Recorders\MailRecorder::class,
        \BinaryBuilds\LaritorClient\Recorders\NotificationRecorder::class,
        \BinaryBuilds\LaritorClient\Recorders\DatabaseSchemaRecorder::class,
    ],

    'query' => [
        'read' => env('LARITOR_RECORD_READ_QUERIES', true),

        'write' => env('LARITOR_RECORD_WRITE_QUERIES', true),

        /**
         * Set the below value to false if you do not want to record queries
         * executed in the console (jobs, commands, scheduled tasks).
         */
        'console' => env('LARITOR_RECORD_CONSOLE_QUERIES', true),

        /**
         *  If you wish to avoid recording query bindings,
         * set the below value to true.
         */
        'bindings' => env('LARITOR_RECORD_QUERY_BINDINGS', true),
    ],

    'anonymize' => [
        'ip' => env('LARITOR_ANONYMIZE_IP', false),
        'user_agent' => env('LARITOR_ANONYMIZE_USER_AGENT', false),
        'user' => env('LARITOR_ANONYMIZE_USER', false),
        'pii' => env('LARITOR_ANONYMIZE_PII', false),
    ],

    'requests' => [

        'query_string' => env('LARITOR_RECORD_QUERY_STRING', false),

        'rate_limit' => [
            'enabled' => env('LARITOR_RATE_LIMIT_REQUESTS', false),

            /**
             * Here you can set the maximum number of requests per url per minute
             * that can be sent to laritor. For example, If you set a value of 5,
             * It will only send 5 requests per minute per url to laritor. Any
             * additional requests will be ignored until the next minute. This
             * will only work if a rate limit is enabled.
             */
            'attempts' => env('LARITOR_RATE_LIMIT_REQUESTS_ATTEMPTS', 5),
        ],

        'ignore' => [
            'telescope/*'.
            'pulse/*',
            '_debugbar*',
            '__clockwork*',
            '_ignition/*',
            'laritor/*',
        ]
    ],

    'outbound_requests' => [
        /**
         * Set the below value to false if you do not want to record outbound requests
         * executed in the console (jobs, commands, scheduled tasks).
         */
        'console' => env('LARITOR_RECORD_CONSOLE_OUTBOUND_REQUESTS', true),

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
        ]
    ],
];