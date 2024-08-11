<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'Access-Control-Allow-Origin' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Authorization', '*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
