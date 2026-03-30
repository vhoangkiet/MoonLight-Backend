<?php

return [
    'paths' => ['api/*', '*'], // Specify the paths that should be allowed for CORS

    'allowed_methods' => ['*'], // Allow all HTTP methods (GET, POST, etc.)

    'allowed_origins' => ['*'], // Allow requests from this origin

    'allowed_origins_patterns' => [], // Optional patterns for allowed origins

    'allowed_headers' => ['*'], // Allow all headers

    'exposed_headers' => ['X-Custom-Code'], // Headers that can be exposed to the browser

    'max_age' => 0, // Maximum age of the CORS preflight request in seconds

    'supports_credentials' => true, // Whether to include credentials (cookies, HTTP authentication) with requests
];
