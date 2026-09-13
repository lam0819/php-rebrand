<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Docs\Persistence\DocPage;
use App\Docs\Support\DocPagePresenter;
use Illuminate\Console\Command;

/**
 * Streams the manual into newline-delimited JSON for the InlaySQL index builder.
 * Only the fields the browser search needs cross the boundary — slug, title,
 * type, purpose and a plain-text excerpt — never the full rendered HTML.
 */
final class ExportSearchIndex extends Command
{
    protected $signature = 'search:export
        {--path= : Destination NDJSON file (defaults to config search export path)}
        {--limit=0 : Export at most this many pages (0 = all)}';

    protected $description = 'Export manual pages to NDJSON for the search index builder';

    public function handle(DocPagePresenter $presenter): int
    {
        $path = (string) ($this->option('path') ?: storage_path('app/search-export.ndjson'));
        $limit = max(0, (int) $this->option('limit'));
        $excerptChars = max(0, (int) config('search.index.excerpt_chars'));

        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0o755, true) && ! is_dir($directory)) {
            $this->error("Unable to create directory [{$directory}].");

            return self::FAILURE;
        }

        $handle = fopen($path, 'wb');
        if ($handle === false) {
            $this->error("Unable to write to [{$path}].");

            return self::FAILURE;
        }

        $count = 0;

        $query = DocPage::query()
            ->select(['id', 'slug', 'title', 'type', 'content', 'metadata'])
            ->orderBy('id');

        if ($limit > 0) {
            $query->limit($limit);
        }

        try {
            foreach ($query->cursor() as $page) {
                $record = [
                    'slug' => (string) $page->slug,
                    'title' => (string) $page->title,
                    'type' => (string) $page->type,
                    'purpose' => (string) ($presenter->purpose($page) ?? ''),
                    'excerpt' => $this->excerpt((string) ($page->content ?? ''), $excerptChars),
                ];

                fwrite($handle, json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
                $count++;
            }
        } finally {
            fclose($handle);
        }

        $this->components->info("Exported {$count} pages to {$path}.");

        return self::SUCCESS;
    }

    private function excerpt(string $content, int $chars): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', $content));

        if ($chars > 0 && mb_strlen($text) > $chars) {
            $text = mb_substr($text, 0, $chars);
        }

        return $text;
    }
}
