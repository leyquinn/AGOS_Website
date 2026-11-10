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

    'agos_scraper' => [
        'python_bin' => env('AGOS_PYTHON_BIN', 'python'),
        'script_path' => env('AGOS_SCRAPER_SCRIPT', base_path('intelligence/scraper/davao_flood_scraper.py')),
        'cleaner_script_path' => env('AGOS_CLEANER_SCRIPT', base_path('intelligence/cleaner/davao_flood_cleaner.py')),
        'model_script_path' => env('AGOS_MODEL_SCRIPT', base_path('intelligence/model/agos_model_pipeline.py')),
        'model_checkpoint_path' => env('AGOS_MODEL_CHECKPOINT', base_path('intelligence/model/checkpoints/best_model.pt')),
        'profile_dir' => env('AGOS_SCRAPER_PROFILE_DIR', is_dir('c:/Users/narp/Desktop/Coding Projects/Thesis/fb_chrome_profile')
            ? 'c:/Users/narp/Desktop/Coding Projects/Thesis/fb_chrome_profile'
            : base_path('intelligence/scraper/fb_chrome_profile')),
    ],

];
