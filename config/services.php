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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', 'https://ica-app.com/auth/google/callback'),
        // 'redirect' => 'http://localhost:8000/auth/google/callback',
    ],

    // App Links / Universal Links: https://www.ica-app.com/check-in/{code} and
    // /sermons/{slug} open in the mobile app when it's installed.
    'app_links' => [
        'paths' => ['/check-in/*', '/sermons/*'],
        'ios_app_ids' => array_filter(explode(',', env('IOS_APP_LINK_APP_IDS', '4BR249SGCL.com.icamalawi.icaapp'))),
        'android_package' => env('ANDROID_APP_LINK_PACKAGE', 'com.icaapp.app'),
        // SHA-256 of the signing certificate(s): Play Console → App integrity → App signing key
        // (plus the upload/debug key for local testing). Comma-separated, AA:BB:… format.
        'android_sha256' => array_filter(array_map('trim', explode(',', env('ANDROID_APP_LINK_SHA256', '')))),
    ],

];
 