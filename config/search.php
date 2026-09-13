<?php

declare(strict_types=1);

return [
    /*
    | The in-browser search engine. The manual is indexed at build time into a
    | single InlaySQL file (vector + BM25), served as a static artifact and
    | queried by the InlaySQL WASM module in the visitor's browser. There is no
    | search server and no PHP database extension involved at runtime.
    */
    'driver' => env('SEARCH_DRIVER', 'wasm'),

    'inlaysql' => [
        // Release whose WASM bundle and file format we build and query with.
        'version' => env('SEARCH_INLAYSQL_VERSION', '0.0.6'),

        // Where the downloaded `pkg/` (inlaysql_wasm.js + _bg.wasm) is unpacked.
        'wasm_path' => env('SEARCH_INLAYSQL_WASM_PATH', storage_path('app/inlaysql/pkg')),

        // The browser fetches the module from here (served as a static asset).
        'public_path' => env('SEARCH_INLAYSQL_PUBLIC_PATH', public_path('inlaysql')),
    ],

    /*
    | The search index artifact. Built by `search:build`, published with the
    | content release, and pulled in place by `search:pull`.
    */
    'index' => [
        'path' => env('SEARCH_INDEX_PATH', storage_path('app/manual-search.inlay')),
        'gz_path' => env('SEARCH_INDEX_GZ_PATH', storage_path('app/manual-search.inlay.gz')),
        'artifact_url' => env(
            'SEARCH_ARTIFACT_URL',
            'https://github.com/lam0819/php-rebrand/releases/latest/download/manual-search.inlay.gz',
        ),

        // Vector width and storage. The embedder is InlaySQL's built-in trigram
        // hashing, run identically at build time (WASM in Node) and in the
        // browser (`embed()`), so the vectors always match.
        'dimensions' => (int) env('SEARCH_DIMENSIONS', 256),
        'int8' => filter_var(env('SEARCH_INT8', true), FILTER_VALIDATE_BOOL),

        // How much plain-text body to carry per page in the browser index.
        'excerpt_chars' => (int) env('SEARCH_EXCERPT_CHARS', 800),

        // Rows per write batch. InlaySQL requires a commit to fit one WAL
        // region (~1 MiB), and batching also cuts the artifact size ~5x.
        'batch_size' => (int) env('SEARCH_BATCH_SIZE', 50),
    ],
];
