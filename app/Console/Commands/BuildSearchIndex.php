<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * Builds the browser search index: exports the manual, then runs the Node
 * builder that writes a single InlaySQL file (BM25 + HNSW vector) using the
 * WASM module's own embedder. The resulting `.inlay` is what visitors download
 * and query in the browser.
 */
final class BuildSearchIndex extends Command
{
    protected $signature = 'search:build
        {--fresh : Rebuild the export and index from scratch}
        {--skip-export : Reuse an existing NDJSON export}
        {--limit=0 : Index only the first N pages (0 = all)}';

    protected $description = 'Build the browser search index (InlaySQL vector + BM25)';

    public function handle(): int
    {
        $wasmDir = (string) config('search.inlaysql.wasm_path');
        $script = base_path('scripts/inlaysql/build-index.mjs');
        $export = storage_path('app/search-export.ndjson');
        $output = (string) config('search.index.path');

        if (! is_file($wasmDir.'/inlaysql_wasm.js')) {
            $this->error('InlaySQL WASM bundle is missing. Run `bash scripts/inlaysql/install-wasm.sh` first.');

            return self::FAILURE;
        }

        if (! is_file($script)) {
            $this->error("Builder script not found at [{$script}].");

            return self::FAILURE;
        }

        if ($this->option('fresh') && is_file($output)) {
            @unlink($output);
        }

        if (! $this->option('skip-export')) {
            if ($this->call('search:export', ['--path' => $export, '--limit' => (int) $this->option('limit')]) !== self::SUCCESS) {
                return self::FAILURE;
            }
        }

        if (! is_file($export)) {
            $this->error("Export file [{$export}] not found. Run without --skip-export.");

            return self::FAILURE;
        }

        $command = [
            'node', $script,
            '--input='.$export,
            '--output='.$output,
            '--wasm-dir='.$wasmDir,
            '--dim='.(int) config('search.index.dimensions'),
            '--batch='.(int) config('search.index.batch_size'),
        ];

        if (config('search.index.int8')) {
            $command[] = '--int8';
        }

        $this->components->info('Building the InlaySQL search index…');

        $result = Process::timeout(1800)->run($command);

        if ($result->failed()) {
            $this->error('Index build failed:');
            $this->line(trim($result->errorOutput()) ?: trim($result->output()));

            return self::FAILURE;
        }

        $this->line(trim($result->errorOutput()));

        if (! is_file($output)) {
            $this->error("Builder finished but [{$output}] was not created.");

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Search index ready: %s (%s).',
            $output,
            $this->humanSize((int) filesize($output)),
        ));

        return self::SUCCESS;
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $i = min($i, count($units) - 1);

        return round($bytes / (1024 ** $i), 1).' '.$units[$i];
    }
}
