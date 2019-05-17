<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'recaptcha' => [
        'key' => env('RECAPTCHA_SITE_KEY'),
        'secret' => env('RECAPTCHA_SECRET_KEY'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_OAUTH_CLIENT_ID'),
        'client_secret' => env('GOOGLE_OAUTH_CLIENT_SECRET'),
        'drive' => [
            'redirect_uri' => env('GOOGLE_DRIVE_REDIRECT_URI')
        ]
    ],

    'dropbox' => [
        'key'    => env('DROPBOX_APP_KEY'),
        'secret' => env('DROPBOX_APP_SECRET')
    ],

    'mailchimp' => [
        'list_id'    => env('MC_LIST_ID'),
        'installed_tag'    => env('MC_TAG_INSTALLED'),
        'uninstalled_tag'    => env('MC_TAG_UNINSTALLED'),
    ],

    'dripapps' => [
        'endpoint' => env('DRIPAPPS_ENDPOINT'),
        'username' => env('DRIPAPPS_USERNAME'),
        'password' => env('DRIPAPPS_PASSWORD'),
    ],

    'canva' => [
        'client_id' => env('CANVA_CLIENT_ID'),
        'client_secret' => env('CANVA_CLIENT_SECRET'),
        'scope' => env('CANVA_SCOPE'),
        'redirect_uri' => env('CANVA_REDIRECT_URI'),
    ]
];
