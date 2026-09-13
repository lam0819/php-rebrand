<?php

declare(strict_types=1);

use App\Docs\Enums\DocumentType;
use App\Docs\Importer\DomXmlDocumentLoader;
use App\Docs\Parser\ChapterParser;
use App\Docs\Parser\RefEntryParser;
use App\Docs\Support\SourceFile;

function parseFixture(object $parser, string $relative)
{
    $document = (new DomXmlDocumentLoader)->load(new SourceFile(fixturePath($relative), $relative));

    return $parser->parse($document);
}

it('parses a refentry into a structured page', function () {
    $page = parseFixture(new RefEntryParser, 'reference/array/array-map.xml');

    expect($page->title)->toBe('array_map')
        ->and($page->type)->toBe(DocumentType::RefEntry)
        ->and($page->metadata->purpose)->toContain('callback')
        ->and($page->parameters)->toHaveCount(3)
        ->and($page->parameters[0]->name)->toBe('callback')
        ->and($page->returnValue?->type)->toBe('array')
        ->and($page->metadata->extra['signature'])->toContain('array_map');
});

it('parses a chapter into a titled narrative page', function () {
    $page = parseFixture(new ChapterParser, 'language/control-structures.xml');

    expect($page->title)->toBe('Control Structures')
        ->and($page->type)->toBe(DocumentType::Chapter)
        ->and($page->content)->toContain('control structures')
        ->and($page->sections)->not->toBeEmpty()
        ->and($page->sections[0]->title)->toBe('if');
});
