<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Web\ChangelogFetcher;
use App\Web\Models\PhpRelease;
use App\Web\WebContentImporter;
use Illuminate\Console\Command;

/**
 * Keeps the site's news, release and changelog data in sync with upstream:
 * fetch php/web-php (news + release config) and the php/php-src NEWS files
 * (changelog), then import. Incremental, so a run with no upstream changes is a
 * cheap no-op.
 */
final class SyncWebContent extends Command
{
    protected $signature = 'web:sync
        {--skip-fetch : Use the already-fetched sources instead of pulling latest}
        {--force : Re-import every item, even unchanged ones}';

    protected $description = 'Sync news, release and changelog data from php/web-php and php/php-src';

    public function handle(WebContentImporter $importer, ChangelogFetcher $changelogFetcher): int
    {
        if (! $this->option('skip-fetch') && $this->call('web:fetch') !== self::SUCCESS) {
            $this->error('Sync aborted: fetch failed.');

            return self::FAILURE;
        }

        $path = config()->string('web.source.path');
        if (! is_dir($path)) {
            $this->error("Source path [{$path}] does not exist. Run `php artisan web:fetch` first.");

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');

        $news = $importer->importNews($path, $force);
        $releases = $importer->importReleases($path, $force);

        // Changelog: pull the NEWS file for each active branch we now know about.
        $branches = PhpRelease::query()->orderByDesc('branch')->get()->map(static fn (PhpRelease $r): string => $r->branch)->all();
        $newsFiles = $this->option('skip-fetch')
            ? $this->existingNewsFiles($branches)
            : $changelogFetcher->fetch($branches);
        $changelog = $importer->importChangelog($newsFiles, $force);

        $this->newLine();
        $this->table(['Content', 'Imported', 'Unchanged'], [
            ['News entries', $news['imported'], $news['skipped']],
            ['Releases', $releases['imported'], $releases['skipped']],
            ['Changelog releases', $changelog['imported'], $changelog['skipped']],
        ]);

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $branches
     * @return array<string, string>
     */
    private function existingNewsFiles(array $branches): array
    {
        $dir = config()->string('web.changelog.path');
        $files = [];
        foreach ($branches as $branch) {
            $path = $dir.'/PHP-'.$branch.'.NEWS';
            if (is_file($path)) {
                $files[$branch] = $path;
            }
        }

        return $files;
    }
}
