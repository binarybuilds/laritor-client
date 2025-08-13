<?php

return [

    /**
     * We have a detailed guide explaining how to update these values
     * on https://laritor.com/docs/customization
     */

    'enabled' => env('LARITOR_ENABLED', false),

    'env' => env('LARITOR_ENV'),

    'ingest_endpoint' => env('LARITOR_INGEST_ENDPOINT'),

    'keys' => [
        'backend' => env('LARITOR_BACKEND_KEY')
    ],

    'server_name' => env('LARITOR_SERVER_NAME'),

    'log_level' => env('LARITOR_LOG_LEVEL', 'debug'),

    'max_events' => env('LARITOR_MAX_EVENTS', 5000),

    'context' => env('LARITOR_RECORD_CONTEXT', true),

    'db_schema' => env('LARITOR_RECORD_DB_SCHEMA', true),

    'query_bindings' => env('LARITOR_RECORD_QUERY_BINDINGS', true),

    'requests' => [

        'query_string' => env('LARITOR_RECORD_QUERY_STRING', false),

        'body' => env('LARITOR_RECORD_REQUEST_BODY', false),

        'headers' => env('LARITOR_RECORD_REQUEST_HEADERS', false),

        'response_headers' => env('LARITOR_RECORD_REQUEST_RESPONSE_HEADERS', false),

        'response_body' => env('LARITOR_RECORD_REQUEST_RESPONSE_BODY', false),

        'rate_limit' => [
            'enabled' => env('LARITOR_RATE_LIMIT_REQUESTS', false),

            'attempts' => env('LARITOR_RATE_LIMIT_REQUESTS_ATTEMPTS', 5),
        ],
    ],

    'outbound_requests' => [

        'body' => env('LARITOR_RECORD_OUTBOUND_REQUEST_BODY', false),

        'headers' => env('LARITOR_RECORD_OUTBOUND_REQUEST_HEADERS', false),

        'response_headers' => env('LARITOR_RECORD_OUTBOUND_REQUEST_RESPONSE_HEADERS', false),

        'response_body' => env('LARITOR_RECORD_OUTBOUND_REQUEST_RESPONSE_BODY', false),
    ],
];