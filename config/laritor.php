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

    'max_events' => env('LARITOR_MAX_EVENTS_PER_OCCURRENCE', 5000),

    'ingest_events_without_occurrence' => env('LARITOR_INGEST_EVENTS_WITHOUT_OCCURRENCE', true),
];