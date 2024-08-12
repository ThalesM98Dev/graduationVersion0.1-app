<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS Options
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Allow all methods (GET, POST, PUT, DELETE, etc.)
    'allowed_methods' => ['*'],

    // Allow all origins
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    // Allow all headers
    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Set to false if you don't need credentials (cookies, HTTP authentication, etc.)
    'supports_credentials' => false,
];
