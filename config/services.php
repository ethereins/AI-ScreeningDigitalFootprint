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

    'ai_service' => [
        'url' => env('AI_SERVICE_URL', 'http://localhost:8000'),
        'timeout' => env('AI_SERVICE_TIMEOUT', 120),
    ],

    'scraper' => [
        'api_url' => env('SCRAPER_API_URL', 'https://api.scrapingbee.com/v1'),
        'api_key' => env('SCRAPER_API_KEY'),
    ],

    'twitter' => [
        'bearer_token' => env('TWITTER_BEARER_TOKEN'),
    ],

    'instagram' => [
        'access_token' => env('INSTAGRAM_ACCESS_TOKEN'),
    ],

    'facebook' => [
        'access_token' => env('FACEBOOK_ACCESS_TOKEN'),
    ],

    'rapidapi' => [
        'key' => env('RAPIDAPI_KEY'),
    ],
];
