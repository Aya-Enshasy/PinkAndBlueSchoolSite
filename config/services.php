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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'speechify' => [
        'key' => env('SPEECHIFY_API_KEY'),
        'base_url' => env('SPEECHIFY_BASE_URL', 'https://api.speechify.ai/v1'),
        'voice_id' => env('SPEECHIFY_VOICE_ID', 'george'),
        'model' => env('SPEECHIFY_MODEL', 'simba-multilingual'),
        'language' => env('SPEECHIFY_LANGUAGE', 'ar-AE'),
        'audio_format' => env('SPEECHIFY_AUDIO_FORMAT', 'mp3'),
        'max_chars' => (int) env('SPEECHIFY_MAX_CHARS', 1900),
        'stream_max_chars' => (int) env('SPEECHIFY_STREAM_MAX_CHARS', 19000),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
