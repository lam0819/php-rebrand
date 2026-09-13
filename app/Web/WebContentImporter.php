<?php

declare(strict_types=1);

namespace App\Web;

use App\Web\DTO\ReleaseData;
use App\Web\Models\ChangelogRelease;
use App\Web\Models\NewsItem;
use App\Web\Models\PhpRelease;
use App\Web\Parsing\NewsEntryParser;
use App\Web\Parsing\NewsFileParser;
use App\Web\Parsing\ReleaseConfigParser;
use DateTimeImmutable;

/**
 * Imports site content (news archive + release config + changelog) from local
 * copies of the php/web-php and php/php-src sources. Like the docs importer, it
 * is incremental: unchanged items are detected by content hash and skipped.
 */
final class WebContentImporter
{
    public function __construct(
        private readonly NewsEntryParser $newsParser,
        private readonly ReleaseConfigParser $releaseParser,
        private readonly NewsFileParser $newsFileParser,
    ) {}

    /**
     * @return array{imported: int, skipped: int}
     */
    public function importNews(string $sourcePath, bool $force = false): array
    {
        $dir = rtrim($sourcePath, '/').'/archive/entries';
        $files = glob($dir.'/*.xml') ?: [];

        $imported = 0;
        $skipped = 0;

        foreach ($files as $file) {
            $xml = (string) file_get_contents($file);
            $hash = hash('xxh128', $xml);
            $entryId = basename($file, '.xml');

            if (! $force && NewsItem::query()->where('entry_id', $entryId)->where('source_hash', $hash)->exists()) {
                $skipped++;

                continue;
            }

            $entry = $this->newsParser->parse($xml, $entryId);

            NewsItem::query()->updateOrCreate(
                ['entry_id' => $entry->entryId],
                [
                    'title' => $entry->title,
                    'category' => $entry->category,
                    'label' => $entry->label,
                    'terms' => $entry->terms,
                    'body_html' => $entry->bodyHtml,
                    'link' => $entry->link,
                    'via' => $entry->via,
                    'published_at' => $entry->publishedAt,
                    'source_hash' => $hash,
                ],
            );
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * @return array{imported: int, skipped: int}
     */
    public function importReleases(string $sourcePath, bool $force = false): array
    {
        $file = rtrim($sourcePath, '/').'/include/version.inc';
        if (! is_file($file)) {
            return ['imported' => 0, 'skipped' => 0];
        }

        $releases = $this->releaseParser->parse((string) file_get_contents($file));

        $imported = 0;
        $skipped = 0;
        $branches = [];

        foreach ($releases as $release) {
            $branches[] = $release->branch;
            $hash = hash('xxh128', serialize([$release->version, $release->date, $release->tags, $release->sha256]));

            if (! $force && PhpRelease::query()->where('branch', $release->branch)->where('source_hash', $hash)->exists()) {
                $skipped++;

                continue;
            }

            PhpRelease::query()->updateOrCreate(
                ['branch' => $release->branch],
                [
                    'version' => $release->version,
                    'released_on' => $this->parseDate($release->date),
                    'tags' => $release->tags,
                    'sha256' => $release->sha256,
                    'source_hash' => $hash,
                ],
            );
            $imported++;
        }

        // Drop branches that upstream no longer lists as active.
        if ($branches !== []) {
            PhpRelease::query()->whereNotIn('branch', $branches)->delete();
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * Import changelog releases from per-branch php-src NEWS files.
     *
     * @param  array<string, string>  $newsFiles  branch => NEWS file path
     * @return array{imported: int, skipped: int}
     */
    public function importChangelog(array $newsFiles, bool $force = false): array
    {
        $imported = 0;
        $skipped = 0;

        foreach ($newsFiles as $path) {
            if (! is_file($path)) {
                continue;
            }

            foreach ($this->newsFileParser->parse((string) file_get_contents($path)) as $release) {
                $hash = hash('xxh128', serialize([$release->version, $release->released, $release->sections]));

                if (! $force && ChangelogRelease::query()->where('version', $release->version)->where('source_hash', $hash)->exists()) {
                    $skipped++;

                    continue;
                }

                ChangelogRelease::query()->updateOrCreate(
                    ['version' => $release->version],
                    [
                        'branch' => $release->branch,
                        'released_on' => $release->date,
                        'released' => $release->released,
                        'sections' => $release->sections,
                        'entry_count' => $release->entryCount(),
                        'source_hash' => $hash,
                    ],
                );
                $imported++;
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    private function parseDate(string $date): ?DateTimeImmutable
    {
        if ($date === '') {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat('d M Y', $date)
            ?: DateTimeImmutable::createFromFormat('j M Y', $date);

        return $parsed === false ? null : $parsed;
    }
}
