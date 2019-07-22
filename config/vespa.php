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

    'model_columns' => array(
        'index' => 'vespa_status',
        'date' => 'vespa_last_indexed_date'
    ),

    'default' => array(
        'limit' => 100,
        'set_language' => 'en-US'
    ),

    'mapped_models' => array(
    	//
    ),

    'stop_words' => []
);
