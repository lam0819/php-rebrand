<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Docs\Persistence\DocPage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Produces a ready-to-serve documentation database: migrate, fetch the latest
 * source, import every page, and build the full-text index — all against the
 * current (SQLite) connection.
 *
 * Run this at build/deploy time so the resulting SQLite file ships as a
 * read-only artifact. The app then needs no database server at runtime.
 */
final class BuildDocs extends Command
{
    protected $signature = 'docs:build
        {--fresh : Drop all tables and rebuild from scratch}
        {--skip-fetch : Use the already-cloned source instead of pulling latest}';

    protected $description = 'Build a deployable, fully-populated docs database (SQLite)';

    public function handle(): int
    {
        $this->components->info('Building the documentation database…');

        $migrate = $this->option('fresh') ? 'migrate:fresh' : 'migrate';
        if ($this->call($migrate, ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if (! $this->option('skip-fetch')) {
            if ($this->call('docs:fetch') !== self::SUCCESS) {
                return self::FAILURE;
            }
        }

        // Forced so a fresh database imports everything (no prior hashes to match).
        if ($this->call('docs:import', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        // Bake the site content (news archive + release data) into the same DB.
        if ($this->call('web:sync', ['--skip-fetch' => $this->option('skip-fetch')]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info(sprintf(
            '%s pages ready in %s.',
            number_format(DocPage::query()->count()),
            $this->databaseLabel(),
        ));

        return self::SUCCESS;
    }

    private function databaseLabel(): string
    {
        $database = DB::connection()->getDatabaseName();

        if (is_file($database)) {
            return $database.' ('.$this->humanSize((int) filesize($database)).')';
        }

        return $database;
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $i = min($i, count($units) - 1);

        return round($bytes / (1024 ** $i), 1).' '.$units[$i];
    }
}
