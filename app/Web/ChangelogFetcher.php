<?php

declare(strict_types=1);

namespace App\Web;

use Illuminate\Support\Facades\Http;

/**
 * Downloads the raw NEWS file for each active PHP branch from php/php-src.
 *
 * php-src is far too large to clone for one file per branch, so we fetch the
 * raw NEWS over HTTP into a local directory the importer then reads.
 */
final class ChangelogFetcher
{
    /**
     * @param  array<int, string>  $branches  e.g. ["8.5", "8.4"]
     * @return array<string, string>  branch => local NEWS file path
     */
    public function fetch(array $branches): array
    {
        $urlTemplate = config()->string('web.changelog.news_url');
        $dir = config()->string('web.changelog.path');

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $files = [];
        foreach ($branches as $branch) {
            $url = str_replace('{branch}', $branch, $urlTemplate);
            $response = Http::timeout(60)->get($url);
            if (! $response->successful()) {
                continue;
            }

            $path = $dir.'/PHP-'.$branch.'.NEWS';
            file_put_contents($path, $response->body());
            $files[$branch] = $path;
        }

        return $files;
    }
}
