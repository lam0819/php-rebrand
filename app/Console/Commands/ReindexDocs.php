<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Docs\Search\DocSearch;
use Illuminate\Console\Command;

/**
 * Rebuilds the FTS5 full-text search index from the docs_pages table.
 */
final class ReindexDocs extends Command
{
    protected $signature = 'docs:reindex';

    protected $description = 'Rebuild the full-text search index from imported pages';

    public function handle(DocSearch $search): int
    {
        if (! $search->available()) {
            $this->warn('Full-text index unavailable (non-SQLite driver); search uses LIKE.');

            return self::SUCCESS;
        }

        $count = $search->rebuild();
        $this->info("Reindexed {$count} pages into the full-text index.");

        return self::SUCCESS;
    }
}
