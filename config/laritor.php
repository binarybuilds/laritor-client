<?php

return [

    /**
     * You can easily enable/disable Laritor by setting the below config value
     * to true or false.
     */
    'enabled' => env('LARITOR_ENABLED', true),

    /**
     * Your laritor project API Keys. If you re-generate the API keys from the dashboard
     *  Make sure you update the below env variable with the new value
     */
    'keys' => [
        'backend' => env('LARITOR_BACKEND_KEY'),
    ],

    /**
     * Here you can enable or disable a specific recorder. If you do not wish a specific recorder to
     * send events to laritor, You can comment it out below. Only recorders which are defined below
     * will send events to laritor.
     */
    'recorders' => [
//      \Laritor\LaravelClient\Recorders\CacheRecorder::class,
      \Laritor\LaravelClient\Recorders\ExceptionRecorder::class,
      \Laritor\LaravelClient\Recorders\OutboundRequestRecorder::class,
      \Laritor\LaravelClient\Recorders\QueryRecorder::class,
      \Laritor\LaravelClient\Recorders\QueuedJobRecorder::class,
      \Laritor\LaravelClient\Recorders\RequestRecorder::class,
      \Laritor\LaravelClient\Recorders\ScheduledCommandRecorder::class,
      \Laritor\LaravelClient\Recorders\SchedulerRecorder::class
    ],

    /**
     * For high traffic sites, some requests which has issues(slow running, slow queries, exceptions, etc) will
     * constantly hit laritor with lots of events. As the number of events in laritor are metered, It is better
     * to rate limit the number of events you send to laritor. If rate limiter is enabled, All requests are
     * rate limited to a given number per minute per request.
     */
    'use_rate_limiter' => true,

    /**
     * Here you can define how many times can a request send events to laritor before hitting the rate limit. For example,
     * if you define a value of 5 below, Any requests to a specific url will send events to laritor upto 5 times every
     * minute.
     * Note: Rate limiting is only by request url. Means events sent from url a will not be counted towards rate limit
     * for events sent from url b. Each url has its own rate limits.
     */
    'rate_limiter_attempts' => 1,

    'query' => [
        /**
         * Below you can define number of milliseconds threshold to determine whether a query is running slow. Means
         * if a query took more than the below mentioned number of milliseconds to execute, Then the said query
         * will be flagged as slow and reported to laritor.
         */
        'slow' => env('LARITOR_SLOW_QUERY', 100 ),

        /**
         * If for any reason, you do not wish to send read queries(SELECT) to laritor, set the below value to false.
         * This will not report any read queries to laritor.
         */
        'read' => true,

        /**
         * Most of the applications primary focus on read query performance. If you wish to also monitor the performance
         * of write(INSERT, UPDATE, DELETE) queries, set the below value to true.
         */
        'write' => false,

        /**
         * Most of the time, queries running in console do not cause any performance issues to the application. If you
         * wish to monitor the queries executed in the console as well, set the below value to true.
         */
        'monitor_console_queries' => false,
    ],

    'requests' => [
        'slow' =>  env('LARITOR_SLOW_REQUESTS', 2000 ),
        'ignore' => [
            'nova/*',
            'nova-api/*',
            'telescope/*'
        ]
    ],

    'outbound_requests' => [
        'slow' =>  env('LARITOR_SLOW_OUTBOUND_REQUESTS', 2000 ),
        'ignore_console_requests' => true,
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