<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Docs\Contracts\DocumentImporter;
use App\Docs\DTO\ImportReport;
use Illuminate\Console\Command;

/**
 * Runs the import pipeline over a documentation source tree and reports what was
 * imported, skipped, and what failed. The actual work is delegated entirely to
 * the {@see DocumentImporter} — this command only resolves the path and renders
 * the resulting {@see ImportReport}.
 */
final class ImportDocs extends Command
{
    protected $signature = 'docs:import
        {--path= : Source directory to import (defaults to config docs.source.path)}
        {--force : Re-import every document, even unchanged ones}
        {--show-failures : List the documents that failed to import}';

    protected $description = 'Import PHP documentation XML into the docs_pages table';

    public function handle(DocumentImporter $importer): int
    {
        $pathOption = $this->option('path');
        $path = is_string($pathOption) && $pathOption !== ''
            ? $pathOption
            : config()->string('docs.source.path');

        if (! is_dir($path)) {
            $this->error("Source path [{$path}] does not exist. Run `php artisan docs:fetch` first.");

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');

        $this->info("Importing from {$path} …".($force ? ' (forced)' : ''));
        $report = $importer->import($path, $force);

        $this->newLine();
        $this->table(['Result', 'Count'], [
            ['Imported', $report->importedCount()],
            ['Unchanged (skipped)', $report->unchangedCount()],
            ['Skipped (unsupported)', $report->skippedCount()],
            ['Failed', $report->failedCount()],
        ]);

        if ($this->option('show-failures') && $report->failedCount() > 0) {
            $this->newLine();
            $this->warn('Failures:');
            foreach ($report->failed() as $path => $reason) {
                $this->line("  - {$path}: {$reason}");
            }
        }

        // Keep the full-text index in lockstep with the pages we just wrote.
        if ($report->importedCount() > 0) {
            $this->call('docs:reindex');
        }

        return self::SUCCESS;
    }
}
