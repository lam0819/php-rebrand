<?php

declare(strict_types=1);

use App\Docs\Enums\DocumentType;
use App\Docs\Exceptions\ParserNotFoundException;
use App\Docs\Importer\DocumentParserRegistry;
use App\Docs\Parser\ChapterParser;
use App\Docs\Parser\RefEntryParser;
use App\Docs\Testing\FakeDocumentParser;

it('resolves a registered parser by document type', function () {
    $registry = new DocumentParserRegistry([new RefEntryParser, new ChapterParser]);

    expect($registry->resolve(DocumentType::RefEntry))->toBeInstanceOf(RefEntryParser::class)
        ->and($registry->resolve(DocumentType::Chapter))->toBeInstanceOf(ChapterParser::class)
        ->and($registry->has(DocumentType::Article))->toBeFalse();
});

it('throws ParserNotFoundException when no parser supports the type', function () {
    (new DocumentParserRegistry)->resolve(DocumentType::RefEntry);
})->throws(ParserNotFoundException::class);

it('honours registration order as precedence', function () {
    $first = new FakeDocumentParser([DocumentType::RefEntry], DocumentType::RefEntry);
    $second = new FakeDocumentParser([DocumentType::RefEntry], DocumentType::Article);

    $registry = new DocumentParserRegistry([$first, $second]);

    expect($registry->resolve(DocumentType::RefEntry))->toBe($first);
});
