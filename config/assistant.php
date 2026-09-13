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

    // Any OpenRouter model id works; the default is a free one. See
    // https://openrouter.ai/models?max_price=0
    'model' => env('ASSISTANT_MODEL', 'nvidia/nemotron-3-super-120b-a12b:free'),

    // How many retrieved pages to pass as context, and how much of each.
    'sources' => (int) env('ASSISTANT_SOURCES', 6),
    'excerpt_chars' => (int) env('ASSISTANT_EXCERPT_CHARS', 900),
    'max_question_chars' => (int) env('ASSISTANT_MAX_QUESTION_CHARS', 500),

    'throttle' => [
        'per_minute' => (int) env('ASSISTANT_PER_MINUTE', 5),
        'per_day' => (int) env('ASSISTANT_PER_DAY', 200),
    ],
];
