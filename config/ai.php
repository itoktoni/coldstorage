<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    |
    | Register all available AI providers here. Each provider has:
    |   - base_url: API endpoint (OpenAI-compatible)
    |   - api_key: authentication key
    |   - models: list of available models with optional defaults
    |
    | Usage in commands:
    |   php artisan generate:idea permainan_edukasi --provider=openai
    |   php artisan generate:activity storytelling kebersamaan --provider=minimax
    |   php artisan generate:image 75 --provider=deepseek
    |
    */

    'providers' => [

        'openai' => [
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com'),
            'api_key'  => env('OPENAI_API_KEY'),
            'models'   => [
                'gpt-4o'              => ['temperature' => 0.7],
                'gpt-4o-mini'         => ['temperature' => 0.7],
                'gpt-3.5-turbo'       => ['temperature' => 0.7],
            ],
        ],

        'minimax' => [
            'base_url' => env('MINIMAX_BASE_URL', 'https://api.minimax.chat/v1'),
            'api_key'  => env('MINIMAX_API_KEY', env('OPENAI_API_KEY')),
            'models'   => [
                'MiniMax-M2.7-highspeed' => ['temperature' => 0.7],
                'MiniMax-M2.7'           => ['temperature' => 0.7],
            ],
        ],

        'deepseek' => [
            'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
            'api_key'  => env('DEEPSEEK_API_KEY', env('OPENAI_API_KEY')),
            'models'   => [
                'deepseek-chat'  => ['temperature' => 0.7],
                'deepseek-coder' => ['temperature' => 0.5],
            ],
        ],

        'groq' => [
            'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
            'api_key'  => env('GROQ_API_KEY', env('OPENAI_API_KEY')),
            'models'   => [
                'llama-3.3-70b-versatile' => ['temperature' => 0.7],
                'llama-3.1-8b-instant'    => ['temperature' => 0.7],
                'mixtral-8x7b-32768'      => ['temperature' => 0.7],
            ],
        ],

        'ollama' => [
            'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434/v1'),
            'api_key'  => env('OLLAMA_API_KEY', 'ollama'),
            'models'   => [
                'llama3'    => ['temperature' => 0.7],
                'mistral'   => ['temperature' => 0.7],
                'gemma'     => ['temperature' => 0.7],
            ],
        ],

        'openrouter' => [
            'base_url' => env('OPEN_ROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
            'api_key'  => env('OPEN_ROUTER_API_KEY', env('OPENAI_API_KEY')),
            'models'   => [
                env('OPEN_ROUTER_MODEL_IDEA', 'cohere/north-mini-code:free') => ['temperature' => 0.7],
                env('OPEN_ROUTER_MODEL_ACTIVITY', 'nvidia/nemotron-3.5-content-safety:free') => ['temperature' => 0.7],
            ],
        ],

        'sumopod' => [
            'base_url' => env('AI_DEFAULT_URL', 'https://ai.sumopod.com/v1'),
            'api_key'  => env('SUMOPOD_API_KEY', env('AI_DEFAULT_KEY')),
            'models'   => [
                env('AI_DEFAULT_MODEL', 'MiniMax-M2.7-highspeed') => ['temperature' => 0.7],
            ],
        ],

        'naraai' => [
            'base_url' => env('AI_DEFAULT_URL', 'https://router.bynara.id/v1'),
            'api_key'  => env('NARAAI_API_KEY', env('AI_DEFAULT_KEY')),
            'models'   => [
                env('AI_DEFAULT_MODEL', 'mimo-v2.5-pro-free') => ['temperature' => 0.7],
            ],
        ],

        'servernusa' => [
            'base_url' => env('AI_DEFAULT_URL', 'https://ai.servernusa.com/v1'),
            'api_key'  => env('SERVERNUSA_API_KEY', env('AI_DEFAULT_KEY')),
            'models'   => [
                env('AI_DEFAULT_MODEL', 'mimo-v2.5-pro') => ['temperature' => 0.7],
            ],
        ],

        'dahono' => [
            'base_url' => env('AI_DEFAULT_URL', 'https://gateway.dahono.com/v1'),
            'api_key'  => env('DAHONO_API_KEY', env('AI_DEFAULT_KEY')),
            'models'   => [
                env('AI_DEFAULT_MODEL', 'dahono/mimo-auto') => ['temperature' => 0.7],
            ],
        ],

        'xiaomi' => [
            'base_url' => env('XIAOMI_BASE_URL', env('AI_DEFAULT_URL', 'https://token-plan-sgp.xiaomimimo.com/v1')),
            'api_key'  => env('XIAOMI_API_KEY', env('AI_DEFAULT_KEY', env('OPENAI_API_KEY'))),
            'models'   => [
                env('AI_DEFAULT_MODEL', 'mimo/mimo-v2.5-pro') => ['temperature' => 0.7],
            ],
        ],

         '9router' => [
            'base_url' => env('9Router_BASE_URL', 'http://localhost:20128/v1'),
            'api_key'  => env('9Router_API_KEY', env('AI_DEFAULT_KEY', env('OPENAI_API_KEY'))),
            'models'   => [
                env('AI_DEFAULT_MODEL', 'mimo/mimo-v2.5-pro') => ['temperature' => 0.7],
            ],
        ],

        'custom' => [
            'base_url' => env('AI_CUSTOM_BASE_URL', 'https://api.openai.com'),
            'api_key'  => env('AI_CUSTOM_API_KEY', env('OPENAI_API_KEY')),
            'models'   => [
                env('AI_CUSTOM_MODEL', 'custom-model') => ['temperature' => 0.7],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    */

    'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),
    'default_model'    => env('AI_DEFAULT_MODEL', null), // null = first model in provider
    'default_url'    => env('AI_DEFAULT_URL', null), // null = first model in provider
    'default_key'    => env('AI_DEFAULT_KEY', null), // null = first model in provider

    /*
    |--------------------------------------------------------------------------
    | Per-Command Default Providers
    |--------------------------------------------------------------------------
    |
    | Each generate command can have its own default provider.
    | Falls back to default_provider if not set.
    |
    */

    'commands' => [
        'idea'       => env('AI_GENERATE_IDEA', null),
        'activity'   => env('AI_GENERATE_ACTIVITY', null),
        'image'      => env('AI_GENERATE_IMAGE', null),
        'marketing'  => env('AI_GENERATE_MARKETING', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Generation Provider
    |--------------------------------------------------------------------------
    */

    'image' => [
        'provider'  => env('IMAGE_AI_PROVIDER', 'openai'),
        'base_url'  => env('IMAGE_BASE_URL', 'https://ark.ap-southeast.bytepluses.com/api/v3'),
        'api_key'   => env('IMAGE_API_KEY'),
        'model'     => env('IMAGE_MODEL', 'seedream-4-5-251128'),
        'size'      => env('IMAGE_SIZE', '2K'),
    ],

];
