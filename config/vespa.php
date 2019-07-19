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

    'hosts' => env('VESPA_HOSTS', 'localhost:8080'),

    'model_columns' => array(
        'index' => 'vespa_status',
        'date' => 'vespa_last_indexed_date'
    ),

    'default' => array(
        'bulk' => 100,
        'set_language' => 'en-US'
    ),

    'mapped_models' => array(
    	//
    ),

    'stop_words' => []
);
