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
];
