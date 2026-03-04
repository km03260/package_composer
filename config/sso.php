<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Single Sign-On (SSO) Configuration
    |--------------------------------------------------------------------------
    |
    | These settings allow your Laravel app to connect to the SSO server.
    | Add the corresponding values in your `.env` file.
    |
    */

    // URL of the SSO server
    'server_url' => env('SSO_SERVER_URL', 'https://sso.example.com'),

    // Client ID assigned to your application by the SSO server
    'client_id' => env('SSO_CLIENT_ID', 'your-client-id'),

    // Client secret assigned to your application by the SSO server
    'client_secret' => env('SSO_CLIENT_SECRET', 'your-client-secret'),

];
