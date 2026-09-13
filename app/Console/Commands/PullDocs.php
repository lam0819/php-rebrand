<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Installs the prebuilt SQLite manual published by the release workflow. The
 * artifact is downloaded from GitHub Releases (see config/docs.php), verified
 * against its sha256 sidecar when available, decompressed, and written in place
 * — the deploy-time alternative to running the whole upstream import.
 */
final class PullDocs extends Command
{
    protected $signature = 'docs:pull
        {--url= : Override the gzipped artifact URL}
        {--path= : Destination SQLite path (defaults to the configured database)}
        {--force : Replace an existing database file}';

    protected $description = 'Download the latest prebuilt documentation SQLite artifact';

    public function handle(): int
    {
        $destination = $this->destination();

        if ($destination === null) {
            $this->error('No SQLite path available. Set DB_DATABASE or pass --path.');

            return self::FAILURE;
        }

        if (is_file($destination) && ! $this->option('force')) {
            $this->components->warn("A database already exists at {$destination}. Use --force to replace it.");

            return self::SUCCESS;
        }

        $url = $this->url();
        $this->components->info("Downloading content artifact from {$url} …");

        $archive = tempnam(sys_get_temp_dir(), 'docs-');
        if ($archive === false) {
            $this->error('Unable to create a temporary file.');

            return self::FAILURE;
        }

        try {
            if (! $this->download($url, $archive)) {
                return self::FAILURE;
            }

            if (! $this->verify($url, $archive)) {
                return self::FAILURE;
            }

            if (! $this->extract($archive, $destination)) {
                return self::FAILURE;
            }
        } finally {
            @unlink($archive);
        }

        $this->components->info(sprintf(
            'Installed %s (%s).',
            $destination,
            $this->humanSize((int) filesize($destination)),
        ));

        return self::SUCCESS;
    }

    private function download(string $url, string $archive): bool
    {
        try {
            $response = Http::timeout(1800)->sink($archive)->get($url);
        } catch (Throwable $exception) {
            $this->error('Download failed: '.$exception->getMessage());

            return false;
        }

        if ($response->failed()) {
            $this->error("Download failed with HTTP {$response->status()}.");

            return false;
        }

        return true;
    }

    /**
     * Verify the archive against the `<url>.sha256` sidecar when it exists.
     * A missing or malformed checksum is a warning, not a failure, so older
     * releases without a sidecar still install.
     */
    private function verify(string $url, string $archive): bool
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

        $actual = hash_file('sha256', $archive);

        if (! hash_equals(strtolower($matches[1]), strtolower((string) $actual))) {
            $this->error('Checksum mismatch — refusing to install the artifact.');

            return false;
        }

        return true;
    }

    private function extract(string $archive, string $destination): bool
    {
        $directory = dirname($destination);

        if (! is_dir($directory) && ! mkdir($directory, 0o755, true) && ! is_dir($directory)) {
            $this->error("Unable to create directory [{$directory}].");

            return false;
        }

        $input = @gzopen($archive, 'rb');
        if ($input === false) {
            $this->error('The downloaded artifact is not valid gzip data.');

            return false;
        }

        $temporary = $destination.'.tmp';
        $output = @fopen($temporary, 'wb');

        if ($output === false) {
            gzclose($input);
            $this->error("Unable to write to [{$temporary}].");

            return false;
        }

        while (! gzeof($input)) {
            $chunk = gzread($input, 1024 * 1024);

            if ($chunk === false) {
                gzclose($input);
                fclose($output);
                @unlink($temporary);
                $this->error('Failed while decompressing the artifact.');

                return false;
            }

            fwrite($output, $chunk);
        }

        gzclose($input);
        fclose($output);

        if (! rename($temporary, $destination)) {
            @unlink($temporary);
            $this->error("Unable to move the artifact into place at [{$destination}].");

            return false;
        }

        return true;
    }

    private function destination(): ?string
    {
        $option = $this->option('path');

        if (is_string($option) && $option !== '') {
            return $option;
        }

        $database = config('database.connections.sqlite.database');

        if (! is_string($database) || $database === '' || $database === ':memory:') {
            return null;
        }

        return $database;
    }

    private function url(): string
    {
        $option = $this->option('url');

        return is_string($option) && $option !== '' ? $option : config()->string('docs.artifact.url');
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $i = min($i, count($units) - 1);

        return round($bytes / (1024 ** $i), 1).' '.$units[$i];
    }
}
