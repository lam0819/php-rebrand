<?php

declare(strict_types=1);

use App\Web\Models\ChangelogRelease;
use App\Web\WebContentImporter;

it('renders the changelog index grouped by branch', function () {
    ChangelogRelease::factory()->create(['version' => '8.5.7', 'branch' => '8.5']);
    ChangelogRelease::factory()->create(['version' => '8.4.22', 'branch' => '8.4']);

    $this->get('/changelog')
        ->assertOk()
        ->assertSee('PHP 8.5', false)
        ->assertSee('PHP 8.4', false)
        ->assertSee('8.5.7', false);
});

it('renders a release page with linked bug references', function () {
    ChangelogRelease::factory()->create([
        'version' => '8.5.7',
        'branch' => '8.5',
        'sections' => [
            ['category' => 'CLI', 'entries' => ['Fixed bug GH-21901 (Stale getopt() optional value).']],
            ['category' => 'URI', 'entries' => ['Fixed CVE-2026-44927.']],
        ],
        'entry_count' => 2,
    ]);

    $res = $this->get('/changelog/8.5.7')->assertOk();

    $res->assertSee('PHP 8.5.7', false)
        ->assertSee('CLI', false)
        ->assertSee('href="https://github.com/php/php-src/issues/21901"', false)
        ->assertSee('GH-21901', false)
        ->assertSee('CVERecord?id=CVE-2026-44927', false);
});

it('links previous and next releases within a branch', function () {
    ChangelogRelease::factory()->create(['version' => '8.5.6', 'branch' => '8.5', 'released_on' => '2026-05-07']);
    ChangelogRelease::factory()->create(['version' => '8.5.7', 'branch' => '8.5', 'released_on' => '2026-06-02']);
    ChangelogRelease::factory()->create(['version' => '8.5.8', 'branch' => '8.5', 'released_on' => '2026-07-02']);

    $this->get('/changelog/8.5.7')
        ->assertOk()
        ->assertSee('changelog/8.5.8', false)  // newer
        ->assertSee('changelog/8.5.6', false); // older
});

it('returns 404 for an unknown version', function () {
    $this->get('/changelog/9.9.9')->assertNotFound();
});

it('imports changelog releases from a NEWS file incrementally', function () {
    $importer = app(WebContentImporter::class);
    $files = ['8.5' => base_path('tests/Fixtures/php-src/PHP-8.5.NEWS')];

    $first = $importer->importChangelog($files);
    expect($first['imported'])->toBeGreaterThan(0);
    expect(ChangelogRelease::query()->where('version', '8.5.7')->exists())->toBeTrue();

    $second = $importer->importChangelog($files);
    expect($second['imported'])->toBe(0)
        ->and($second['skipped'])->toBeGreaterThan(0);
});
