<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->destination = tempnam(sys_get_temp_dir(), 'docs-test-');
    @unlink($this->destination);
});

afterEach(function () {
    @unlink($this->destination);
    @unlink($this->destination.'.tmp');
});

it('downloads, verifies and installs the prebuilt database', function () {
    $payload = 'SQLite format 3'.str_repeat("\0", 128);
    $archive = gzencode($payload);
    $url = 'https://example.test/database.sqlite.gz';

    Http::fake([
        $url => Http::response($archive),
        $url.'.sha256' => Http::response(hash('sha256', $archive).'  database.sqlite.gz'),
    ]);

    $this->artisan('docs:pull', [
        '--url' => $url,
        '--path' => $this->destination,
        '--force' => true,
    ])->assertSuccessful();

    expect(file_get_contents($this->destination))->toBe($payload);
});

it('refuses to install when the checksum does not match', function () {
    $url = 'https://example.test/database.sqlite.gz';

    Http::fake([
        $url => Http::response(gzencode('some data')),
        $url.'.sha256' => Http::response(str_repeat('a', 64)),
    ]);

    $this->artisan('docs:pull', [
        '--url' => $url,
        '--path' => $this->destination,
        '--force' => true,
    ])->assertFailed();

    expect($this->destination)->not->toBeFile();
});

it('keeps an existing database unless forced', function () {
    file_put_contents($this->destination, 'existing');

    Http::fake();

    $this->artisan('docs:pull', ['--path' => $this->destination])
        ->assertSuccessful();

    expect(file_get_contents($this->destination))->toBe('existing');

    Http::assertNothingSent();
});
