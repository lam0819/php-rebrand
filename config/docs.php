<?php

declare(strict_types=1);

return [
    /*
    | The upstream documentation repository (our source of truth) and the local
    | path it is cloned to. We parse this XML directly — not via php/doc-base
    | or php/phd.
    */
    'source' => [
        'repository' => env('DOCS_SOURCE_REPO', 'https://github.com/php/doc-en'),
        'branch' => env('DOCS_SOURCE_BRANCH', 'master'),
        'path' => env('DOCS_SOURCE_PATH', storage_path('app/php-doc-en')),
    ],

    /*
    | The prebuilt, ready-to-serve SQLite manual published by the release
    | workflow (see .github/workflows/content-release.yml). `docs:pull` downloads
    | this gzipped artifact and drops it in place, so a host like Laravel Cloud
    | can install the latest content at build time without cloning upstream.
    */
    'artifact' => [
        'url' => env(
            'DOCS_ARTIFACT_URL',
            'https://github.com/lam0819/php-rebrand/releases/latest/download/database.sqlite.gz',
        ),
    ],
];
