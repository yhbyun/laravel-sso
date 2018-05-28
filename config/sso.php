<?php

return [
    /*
    |--------------------------------------------------------------------------
    | for server
    |--------------------------------------------------------------------------
    */

    'brokers_table' => env('SSO_BROKERS_TABLE', 'sso_brokers'),
    'endpoint_path' => env('SSO_SERVER_ENDPOINT_PATH', '/'),
    'custom_server' => env('SSO_CUSTOM_SERVER', null),

    /*
    |--------------------------------------------------------------------------
    | for both server & broker
    |--------------------------------------------------------------------------
    */

    'username_field' => env('SSO_USERNAME_FIELD', 'email'),

    /*
    |--------------------------------------------------------------------------
    | for broker
    |--------------------------------------------------------------------------
    */

    'server_endpoint' => env('SSO_SERVER_ENDPOINT', null),
    'broker_id' => env('SSO_BROKER_ID', null),
    'broker_secret' => env('SSO_BROKER_SECRET', null),
    // whether get user info from local model ater SSO successful login
    'refer_user_model' => env('SSO_REFER_USER_MODEL', true),
];
