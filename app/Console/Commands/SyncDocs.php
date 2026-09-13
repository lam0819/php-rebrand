<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * One-shot "keep the site in sync with upstream": fetch the latest php/doc-en
 * and re-import only what changed. Safe to run on a schedule — unchanged
 * documents are skipped by content hash, so a no-op run is cheap.
 */
final class SyncDocs extends Command
{
    protected $signature = 'docs:sync
        {--force : Re-import every document, even unchanged ones}';

    protected $description = 'Fetch the latest PHP docs source and import the changes';

    public function handle(): int
    {
        if ($this->call('docs:fetch') !== self::SUCCESS) {
            $this->error('Sync aborted: fetch failed.');

            return self::FAILURE;
        }

        return $this->call('docs:import', [
            '--force' => (bool) $this->option('force'),
        ]);
    }
}
