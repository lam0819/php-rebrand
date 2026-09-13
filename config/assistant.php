<?php

declare(strict_types=1);

return [
    /*
    | The "Ask AI" assistant. It answers questions from the manual: the browser
    | retrieves the most relevant pages from the InlaySQL WASM index, then the
    | server asks an OpenRouter model to answer strictly from those pages and
    | cite them. Disabled automatically when no API key is configured.
    */
    'enabled' => filled(env('OPENROUTER_API_KEY')),

    'provider' => env('ASSISTANT_PROVIDER', 'openrouter'),

    // Up to three OpenRouter models, tried in order. The next is used when the
    // previous one does not start answering within `first_token_timeout`
    // seconds or errors out. Any model ids work; these are free ones.
    // See https://openrouter.ai/models?max_price=0
    'models' => array_values(array_filter([
        env('ASSISTANT_MODEL', 'nvidia/nemotron-3-super-120b-a12b:free'),
        env('ASSISTANT_MODEL_FALLBACK_1', 'google/gemma-4-31b-it:free'),
        env('ASSISTANT_MODEL_FALLBACK_2', 'nex-agi/nex-n2.5-mini:free'),
    ])),

    // How long a model may take to emit its first token before we move on.
    'first_token_timeout' => (float) env('ASSISTANT_FIRST_TOKEN_TIMEOUT', 3),

    // Hard cap on one model's full answer.
    'total_timeout' => (int) env('ASSISTANT_TOTAL_TIMEOUT', 30),

    // Hard cap across every attempt, so a chain of slow models can never reach
    // the gateway's timeout (which surfaces as a 504 to the visitor).
    'deadline' => (float) env('ASSISTANT_DEADLINE', 45),

    // How many retrieved pages to pass as context, and how much of each.
    'sources' => (int) env('ASSISTANT_SOURCES', 8),
    'excerpt_chars' => (int) env('ASSISTANT_EXCERPT_CHARS', 1200),
    'max_question_chars' => (int) env('ASSISTANT_MAX_QUESTION_CHARS', 500),

    'throttle' => [
        'per_minute' => (int) env('ASSISTANT_PER_MINUTE', 5),
        'per_day' => (int) env('ASSISTANT_PER_DAY', 200),
    ],
];
