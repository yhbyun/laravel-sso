<?php

return [
    'brokers_table' => env('SSO_BROKERS_TABLE', 'sso_brokers'),
    'endpoint_path' => env('SSO_SERVER_ENDPOINT_PATH', '/'),
    'server_endpoint' => env('SSO_SERVER_ENDPOINT', null),
    'broker_id' => env('SSO_BROKER_ID', null),
    'broker_secret' => env('SSO_BROKER_SECRET', null),
    'username_field' => env('SSO_USERNAME_FIELD', 'email'),
    'custom_server' => env('SSO_CUSTOM_SERVER', null),
];
