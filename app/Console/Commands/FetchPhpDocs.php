<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * Clones (or updates) the official PHP documentation repository — our source of
 * truth — into the local path configured in config/docs.php. A shallow clone
 * keeps it fast; re-running fast-forwards to the latest commit.
 */
final class FetchPhpDocs extends Command
{
    protected $signature = 'docs:fetch
        {--repo= : Override the source repository URL}
        {--branch= : Override the branch to fetch}
        {--path= : Override the local destination path}';

    protected $description = 'Fetch the latest PHP documentation source (php/doc-en) for import';

    public function handle(): int
    {
        $repo = $this->resolve('repo', 'docs.source.repository');
        $branch = $this->resolve('branch', 'docs.source.branch');
        $path = $this->resolve('path', 'docs.source.path');

        if (Process::run(['git', '--version'])->failed()) {
            $this->error('git is not available on this system.');

            return self::FAILURE;
        }

        if (is_dir($path.'/.git')) {
            $this->info("Updating existing checkout at {$path} …");
            $result = Process::path($path)->timeout(600)->run(['git', 'pull', '--ff-only']);
        } else {
            $this->info("Cloning {$repo} ({$branch}) into {$path} …");
            $result = Process::timeout(1800)->run([
                'git', 'clone', '--depth', '1', '--branch', $branch, $repo, $path,
            ]);
        }

        if ($result->failed()) {
            $this->error('Fetch failed: '.trim($result->errorOutput()));

            return self::FAILURE;
        }

        $this->info("Done. Source ready at {$path}");
        $this->line('Next: <info>php artisan docs:import</info>');

        return self::SUCCESS;
    }

    /**
     * Use the CLI option when given, otherwise fall back to config.
     */
    private function resolve(string $option, string $configKey): string
    {
        $value = $this->option($option);

        return is_string($value) && $value !== '' ? $value : config()->string($configKey);
    }
}
