<?php

declare(strict_types=1);

use App\Docs\Enums\DocumentType;
use App\Docs\Importer\DomXmlDocumentLoader;
use App\Docs\Importer\RootElementDocumentClassifier;
use App\Docs\Support\SourceFile;

function classifyFixture(string $relative): DocumentType
{
    $document = (new DomXmlDocumentLoader)->load(new SourceFile(fixturePath($relative), $relative));

    return (new RootElementDocumentClassifier)->classify($document);
}

it('classifies documents by their root element', function (string $relative, DocumentType $expected) {
    expect(classifyFixture($relative))->toBe($expected);
})->with([
    'refentry' => ['reference/array/array-map.xml', DocumentType::RefEntry],
    'chapter' => ['language/control-structures.xml', DocumentType::Chapter],
    'article' => ['guides/getting-started.xml', DocumentType::Article],
    'appendix' => ['appendix/migration85.xml', DocumentType::Appendix],
]);

it('falls back to Unknown for unrecognised root elements', function () {
    expect(classifyFixture('unknown/colophon.xml'))->toBe(DocumentType::Unknown)
        ->and(DocumentType::Unknown->isKnown())->toBeFalse();
});
