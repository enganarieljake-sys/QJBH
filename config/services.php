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

    'cron' => [
        'secret' => env('CRON_SECRET'),
    ],

    'webpush' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:enganarieljake@gmail.com'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    // QJ Bot's AI providers, tried in this order before OpenRouter. All speak
    // the OpenAI chat API; one without a key is skipped. daily_limit null means
    // the provider limits per minute, not per day.
    'ai_providers' => [
        'nvidia' => [
            'key' => env('NVIDIA_API_KEY'),
            'url' => env('NVIDIA_URL', 'https://integrate.api.nvidia.com/v1'),
            'models' => env('NVIDIA_MODELS', 'nvidia/nemotron-3-ultra-550b-a55b,nvidia/nemotron-3.5-lightning-30b-a3b,deepseek-ai/deepseek-v4.1-flash'),
            'daily_limit' => null,
            // Skip Nemotron's long hidden reasoning: answers in ~2s instead of ~5s.
            'extra' => ['chat_template_kwargs' => ['enable_thinking' => false]],
        ],
        'groq' => [
            'key' => env('GROQ_API_KEY'),
            'url' => env('GROQ_URL', 'https://api.groq.com/openai/v1'),
            'models' => env('GROQ_MODELS', 'llama-3.3-70b-versatile,openai/gpt-oss-120b,qwen/qwen3-32b'),
            // Requests a day per model; corrected from Groq's own headers once known.
            'daily_limit' => (int) env('GROQ_DAILY_LIMIT', 1000),
            'extra' => [],
        ],
    ],

    // QJ Bot's fallback AI. Only free models are ever used; see
    // App\Support\Assistant\OpenRouter.
    'openrouter' => [
        'key' => env('OPENROUTER_API_KEY'),
        'url' => env('OPENROUTER_URL', 'https://openrouter.ai/api/v1'),
        'model' => env('OPENROUTER_MODEL', 'nvidia/nemotron-3-ultra-550b-a55b:free'),
        'fallbacks' => env('OPENROUTER_FALLBACK_MODELS', 'nvidia/nemotron-3-super-120b-a12b:free,stealth/space-bunny-alpha,qwen/qwen3.8-27b:free,nvidia/nemotron-3.5-lightning:free,google/gemma-4-31b-it:free,openrouter/free'),
        // OpenRouter's free tier allows 50 requests a day per account.
        'daily_limit' => (int) env('OPENROUTER_DAILY_LIMIT', 50),
    ],

    // Where the "reset all data" verification code is emailed. Falls back to
    // the signed-in admin's own email when empty.
    'data_reset' => [
        'email' => env('RESET_CODE_EMAIL', 'enganarieljake@gmail.com'),
    ],

];
