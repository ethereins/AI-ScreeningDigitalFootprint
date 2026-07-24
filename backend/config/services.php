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


    'brightdata' => [
        'api_key' => env('BRIGHTDATA_API_KEY'),
        'api_url' => 'https://api.brightdata.com/datasets/v3',

        'datasets' => [
            // ========== INSTAGRAM ==========
            'instagram' => env('BRIGHTDATA_DATASET_INSTAGRAM_POSTS', env('BRIGHTDATA_DATASET_INSTAGRAM')),
            'instagram_posts' => env('BRIGHTDATA_DATASET_INSTAGRAM_POSTS', env('BRIGHTDATA_DATASET_INSTAGRAM')),
            'instagram_profiles' => env('BRIGHTDATA_DATASET_INSTAGRAM', env('BRIGHTDATA_DATASET_INSTAGRAM_POSTS')),
            'instagram_reels' => env('BRIGHTDATA_DATASET_INSTAGRAM_REELS'),
            'instagram_comments' => env('BRIGHTDATA_DATASET_INSTAGRAM_COMMENTS'),

            // ========== TWITTER ==========
            'twitter' => env('BRIGHTDATA_DATASET_TWITTER'),
            'twitter_posts' => env('BRIGHTDATA_DATASET_TWITTER_POSTS'),

            // ========== TIKTOK ==========
            'tiktok' => env('BRIGHTDATA_DATASET_TIKTOK'),
            'tiktok_posts' => env('BRIGHTDATA_DATASET_TIKTOK_POSTS'),
            'tiktok_comments' => env('BRIGHTDATA_DATASET_TIKTOK_COMMENTS'),
        ],
    ],
];
