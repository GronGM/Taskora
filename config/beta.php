<?php

return [
    'enabled' => env('BETA_ACCESS_ENABLED', false),
    'password' => env('BETA_ACCESS_PASSWORD'),
    'cookie_name' => env('BETA_ACCESS_COOKIE_NAME', 'taskora_beta_access'),
];
