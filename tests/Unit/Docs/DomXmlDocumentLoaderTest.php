<?php

declare(strict_types=1);

use App\Docs\Exceptions\InvalidXmlException;
use App\Docs\Importer\DomXmlDocumentLoader;
use App\Docs\Support\LoadedDocument;
use App\Docs\Support\SourceFile;

function loadFixture(string $relative): LoadedDocument
{
    $file = new SourceFile(fixturePath($relative), $relative);

    return (new DomXmlDocumentLoader)->load($file);
}

it('loads a well-formed document and exposes its root element', function () {
    $document = loadFixture('reference/array/array-map.xml');

    expect($document)->toBeInstanceOf(LoadedDocument::class)
        ->and($document->rootElementName())->toBe('refentry')
        ->and($document->rawXml())->toContain('array_map');
});

it('throws InvalidXmlException for malformed xml', function () {
    loadFixture('broken/malformed.xml');
})->throws(InvalidXmlException::class);

it('throws InvalidXmlException for a missing file', function () {
    (new DomXmlDocumentLoader)->load(new SourceFile('/no/such/file.xml', 'no/such/file.xml'));
})->throws(InvalidXmlException::class);
