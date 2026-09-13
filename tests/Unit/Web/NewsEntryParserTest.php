<?php

declare(strict_types=1);

use App\Web\Parsing\NewsEntryParser;

it('parses a release announcement entry', function () {
    $xml = file_get_contents(base_path('tests/Fixtures/web-php/archive/entries/2026-06-04-1.xml'));

    $entry = (new NewsEntryParser)->parse($xml, '2026-06-04-1');

    expect($entry->entryId)->toBe('2026-06-04-1')
        ->and($entry->title)->toBe('PHP 8.5.7 Released!')
        ->and($entry->publishedAt->format('Y-m-d'))->toBe('2026-06-04')
        ->and($entry->category)->toBe('releases')
        ->and($entry->label)->toBe('New PHP release')
        ->and($entry->terms)->toHaveCount(2)
        ->and($entry->bodyHtml)->toContain('immediate availability of PHP 8.5.7')
        ->and($entry->bodyHtml)->toStartWith('<p>')
        ->and($entry->bodyHtml)->not->toContain('<div');
});

it('parses a call-for-papers entry with a via link', function () {
    $xml = file_get_contents(base_path('tests/Fixtures/web-php/archive/entries/2026-05-11-1.xml'));

    $entry = (new NewsEntryParser)->parse($xml, '2026-05-11-1');

    expect($entry->category)->toBe('cfp')
        ->and($entry->label)->toBe('Call for Papers')
        ->and($entry->via)->toBe('https://cfp.longhornphp.com/')
        ->and($entry->bodyHtml)->toContain('Longhorn PHP');
});
