<?php

declare(strict_types=1);

return [
    /*
    | The website-content repository (php/web-php — the source of php.net itself)
    | and the local path it is cloned to. We parse the news archive and the
    | release config straight from this source so the site stays in lockstep
    | with upstream. This is a separate source of truth from the manual XML
    | (see config/docs.php).
    */
    'source' => [
        'repository' => env('WEB_SOURCE_REPO', 'https://github.com/php/web-php'),
        'branch' => env('WEB_SOURCE_BRANCH', 'master'),
        'path' => env('WEB_SOURCE_PATH', storage_path('app/php-web')),

        // We only need these two directories; a sparse checkout keeps the clone tiny.
        'sparse_paths' => ['archive/entries', 'include'],
    ],

    /*
    | The changelog is sourced from the per-branch NEWS files in php/php-src (the
    | same data php.net's ChangeLog-N.php pages are built from). php-src is huge,
    | so rather than clone it we fetch just the raw NEWS file for each active
    | branch over HTTP. {branch} is replaced with e.g. "8.5".
    */
    'changelog' => [
        'news_url' => env('PHP_SRC_NEWS_URL', 'https://raw.githubusercontent.com/php/php-src/PHP-{branch}/NEWS'),
        'path' => storage_path('app/php-src-news'),
    ],
];
