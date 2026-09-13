<?php

declare(strict_types=1);

use App\Web\Models\NewsItem;
use App\Web\Models\PhpRelease;
use App\Web\WebContentImporter;

beforeEach(function () {
    $this->path = base_path('tests/Fixtures/web-php');
    $this->importer = app(WebContentImporter::class);
});

it('imports news entries from the archive', function () {
    $result = $this->importer->importNews($this->path);

    expect($result['imported'])->toBe(2);
    expect(NewsItem::query()->count())->toBe(2);

    $release = NewsItem::query()->where('entry_id', '2026-06-04-1')->firstOrFail();
    expect($release->title)->toBe('PHP 8.5.7 Released!')
        ->and($release->category)->toBe('releases')
        ->and($release->published_at->format('Y-m-d'))->toBe('2026-06-04');
});

it('imports the current release of each active branch', function () {
    $result = $this->importer->importReleases($this->path);

    expect($result['imported'])->toBe(4);
    expect(PhpRelease::query()->count())->toBe(4);

    $current = PhpRelease::query()->where('branch', '8.5')->firstOrFail();
    expect($current->version)->toBe('8.5.7')
        ->and($current->tags)->toBe([])
        ->and($current->sha256['tar.gz'])->toHaveLength(64);

    expect(PhpRelease::query()->where('branch', '8.3')->firstOrFail()->isSecurityRelease())->toBeTrue();
});

it('skips unchanged items on a second run', function () {
    $this->importer->importNews($this->path);
    $second = $this->importer->importNews($this->path);

    expect($second['imported'])->toBe(0)
        ->and($second['skipped'])->toBe(2);

    $this->importer->importReleases($this->path);
    $secondReleases = $this->importer->importReleases($this->path);
    expect($secondReleases['imported'])->toBe(0)
        ->and($secondReleases['skipped'])->toBe(4);
});

it('reimports everything when forced', function () {
    $this->importer->importNews($this->path);
    $forced = $this->importer->importNews($this->path, force: true);

    expect($forced['imported'])->toBe(2)
        ->and(NewsItem::query()->count())->toBe(2);
});

it('prunes branches no longer present upstream', function () {
    PhpRelease::factory()->create(['branch' => '7.4', 'version' => '7.4.33']);

    $this->importer->importReleases($this->path);

    expect(PhpRelease::query()->where('branch', '7.4')->exists())->toBeFalse()
        ->and(PhpRelease::query()->count())->toBe(4);
});
