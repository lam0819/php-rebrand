<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * Clones (or updates) the official php/web-php repository — the source of the
 * php.net site itself — into the local path from config/web.php. Uses a sparse,
 * blobless clone limited to the news archive and release includes, so the
 * checkout stays small and fast.
 */
final class FetchWebContent extends Command
{
    protected $signature = 'web:fetch
        {--repo= : Override the source repository URL}
        {--branch= : Override the branch to fetch}
        {--path= : Override the local destination path}';

    protected $description = 'Fetch the latest php/web-php source (news archive + release config)';

    public function handle(): int
    {
        $repo = $this->resolve('repo', 'web.source.repository');
        $branch = $this->resolve('branch', 'web.source.branch');
        $path = $this->resolve('path', 'web.source.path');
        /** @var array<int, string> $sparsePaths */
        $sparsePaths = config()->array('web.source.sparse_paths');

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
                'git', 'clone', '--depth', '1', '--filter=blob:none', '--sparse',
                '--branch', $branch, $repo, $path,
            ]);

            if ($result->successful()) {
                $result = Process::path($path)->timeout(600)->run(
                    array_merge(['git', 'sparse-checkout', 'set'], $sparsePaths),
                );
            }
        }

        if ($result->failed()) {
            $this->error('Fetch failed: '.trim($result->errorOutput()));

            return self::FAILURE;
        }

        $this->info("Done. Source ready at {$path}");
        $this->line('Next: <info>php artisan web:sync --skip-fetch</info>');

        return self::SUCCESS;
    }

    private function resolve(string $option, string $configKey): string
    {
        $value = $this->option($option);

        return is_string($value) && $value !== '' ? $value : config()->string($configKey);
    }
}
