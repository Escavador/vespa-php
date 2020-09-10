<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | Custom Vespa Client Configuration
    |--------------------------------------------------------------------------
    |
    | This array will be passed to the Vespa client.
    |
    */

    'host' => env('VESPA_HOST', 'localhost:8080'),

    'model_columns' => [
        'status' => 'vespa_status',
        'date' => 'vespa_last_indexed_date'
    ],

    'default' => array(
        'client' => \Escavador\Vespa\Models\VespaRESTClient::class,
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'limit' => 500,
        'bulk' => 100,
        'queue' => 'vespa',
        'max_parallel_requests' => [
            'feed' => 500,
            'update' => 1000
        ],
        'set_language' => 'en-US',
        'tags' => [
            'open' => '<hi>',
            'close' => '</hi>'
        ]
    ),

    'namespace' => [
        'foo' => [
            'document' => [
                [
                    'type' => 'bar',
                    'class' => 'foobar',
                    'table' => 'bazz'
                ],
                //...
            ]
        ]
    ],
    'log' => [
        'channel' => 'vespa'
    ],
    'observers' => [
        'exceptions' => []
    ]
);
