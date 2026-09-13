<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Installs the prebuilt browser search index published with the content
 * release. The gzipped InlaySQL artifact is downloaded, verified against its
 * sha256 sidecar, and kept for the /manual-search.inlay route to stream (the
 * browser gets it already decompressed via Content-Encoding).
 */
final class PullSearchIndex extends Command
{
    protected $signature = 'search:pull
        {--url= : Override the gzipped artifact URL}
        {--force : Replace an existing index}';

    protected $description = 'Download the prebuilt browser search index (InlaySQL artifact)';

    public function handle(): int
    {
        $gzPath = config()->string('search.index.gz_path');
        $url = $this->url();

        if (is_file($gzPath) && ! $this->option('force')) {
            $this->components->warn("A search index already exists at {$gzPath}. Use --force to replace it.");

            return self::SUCCESS;
        }

        $this->components->info("Downloading search index from {$url} …");

        try {
            $response = Http::timeout(1800)->get($url);
        } catch (Throwable $exception) {
            $this->error('Download failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        if ($response->failed()) {
            $this->error("Download failed with HTTP {$response->status()}.");

            return self::FAILURE;
        }

        $body = $response->body();

        if (! $this->verify($url, $body)) {
            return self::FAILURE;
        }

        if (! is_dir(dirname($gzPath))) {
            mkdir(dirname($gzPath), 0o755, true);
        }

        file_put_contents($gzPath, $body);

        $this->components->info(sprintf(
            'Search index ready: %s (%s).',
            $gzPath,
            $this->humanSize(strlen($body)),
        ));

        return self::SUCCESS;
    }

    /**
     * Verify against the `<url>.sha256` sidecar when present. A missing or
     * malformed checksum is a warning, not a failure.
     */
    private function verify(string $url, string $body): bool
    {
        try {
            $response = Http::timeout(60)->get($url.'.sha256');
        } catch (Throwable) {
            $response = null;
        }

        if ($response === null || $response->failed()) {
            $this->components->warn('Checksum sidecar unavailable; skipping verification.');

            return true;
        }

        if (! preg_match('/\b([a-f0-9]{64})\b/i', $response->body(), $matches)) {
            $this->components->warn('Checksum sidecar malformed; skipping verification.');

            return true;
        }

        if (! hash_equals(strtolower($matches[1]), hash('sha256', $body))) {
            $this->error('Checksum mismatch — refusing to install the search index.');

            return false;
        }

        return true;
    }

    private function url(): string
    {
        $option = $this->option('url');

        return is_string($option) && $option !== '' ? $option : config()->string('search.index.artifact_url');
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $i = min($i, count($units) - 1);

        return round($bytes / (1024 ** $i), 1).' '.$units[$i];
    }
}
