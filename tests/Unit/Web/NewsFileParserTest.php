<?php

declare(strict_types=1);

use App\Web\Parsing\NewsFileParser;

beforeEach(function () {
    $this->releases = (new NewsFileParser)->parse(
        (string) file_get_contents(base_path('tests/Fixtures/php-src/PHP-8.5.NEWS')),
    );
    $this->byVersion = collect($this->releases)->keyBy('version');
});

it('parses each release section with its version, branch and date', function () {
    expect($this->byVersion)->toHaveKey('8.5.7');

    $r = $this->byVersion['8.5.7'];
    expect($r->version)->toBe('8.5.7')
        ->and($r->branch)->toBe('8.5')
        ->and($r->released)->toBeTrue()
        ->and($r->date?->format('Y-m-d'))->toBe('2026-06-02');
});

it('flags an in-development "?? ??? ????" header as unreleased', function () {
    $dev = collect($this->releases)->firstWhere('released', false);

    expect($dev)->not->toBeNull()
        ->and($dev->date)->toBeNull();
});

it('groups entries under their category and strips the author attribution', function () {
    $sections = collect($this->byVersion['8.5.7']->sections)->keyBy('category');

    expect($sections)->toHaveKey('CLI');

    $cli = $sections['CLI']['entries'][0];
    expect($cli)->toContain('Fixed bug GH-21901')
        ->and($cli)->toContain('Stale getopt() optional value')
        ->and($cli)->not->toContain('(onthebed)'); // author stripped
});

it('joins continuation lines into a single entry', function () {
    // A multi-line entry should read as one sentence, not be split.
    $allEntries = collect($this->releases)
        ->flatMap(fn ($r) => collect($r->sections)->flatMap(fn ($s) => $s['entries']));

    expect($allEntries->every(fn (string $e) => ! str_starts_with($e, '.')))->toBeTrue();
});
