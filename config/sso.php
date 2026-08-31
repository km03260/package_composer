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

    // Diagnostic de la connexion SSO. Actif, il trace chaque connexion
    // reussie (host, scheme, id de session) et affiche sur la page de login
    // la raison exacte d'un retour en invite : cookie rejete, session perdue,
    // changement de domaine... A n'activer que le temps d'un diagnostic.
    'debug' => env('SSO_DEBUG', false),

    // Public path (relative to the app's public/ dir) of the Gedivepro logo
    // displayed at the top of the login modals.
    'logo_url' => env('SSO_LOGO_URL', 'images/Gedivepro_logo.png'),

];
