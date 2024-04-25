<?php

return [

    'keys' => [
        'backend' => env('LARITOR_BACKEND_KEY'),
    ],

    'query' => [
        'slow' => env('LARITOR_SLOW_QUERY', 1000 )
    ]
];