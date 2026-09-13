<?php

declare(strict_types=1);

use App\Docs\Enums\DocumentType;
use App\Docs\Importer\DocumentParserRegistry;
use App\Docs\Importer\DomXmlDocumentLoader;
use App\Docs\Importer\RecursiveXmlFileFinder;
use App\Docs\Importer\RootElementDocumentClassifier;
use App\Docs\Normalizer\DefaultContentNormalizer;
use App\Docs\Services\ImporterService;
use App\Docs\Testing\FakeDocumentParser;
use App\Docs\Testing\InMemoryDocsPageRepository;

function buildImporter(InMemoryDocsPageRepository $repository): ImporterService
{
    $registry = new DocumentParserRegistry([
        new FakeDocumentParser([
            DocumentType::RefEntry,
            DocumentType::Chapter,
            DocumentType::Article,
            DocumentType::Appendix,
        ]),
    ]);

    return new ImporterService(
        finder: new RecursiveXmlFileFinder,
        loader: new DomXmlDocumentLoader,
        classifier: new RootElementDocumentClassifier,
        parsers: $registry,
        normalizer: new DefaultContentNormalizer,
        repository: $repository,
    );
}

it('coordinates discovery, classification, parsing and persistence end to end', function () {
    $repository = new InMemoryDocsPageRepository;

    $report = buildImporter($repository)->import(fixturePath());

    // 4 supported fixtures imported, 1 unknown skipped, 1 malformed failed.
    expect($report->importedCount())->toBe(4)
        ->and($report->skippedCount())->toBe(1)
        ->and($report->failedCount())->toBe(1)
        ->and($repository->count())->toBe(4)
        ->and($repository->existsForDocId('reference.array.array-map'))->toBeTrue();
});

it('skips unchanged documents on a second import (incremental)', function () {
    $repository = new InMemoryDocsPageRepository;
    $importer = buildImporter($repository);

    $first = $importer->import(fixturePath());
    $second = $importer->import(fixturePath());

    // Nothing changed on disk, so the re-run imports nothing and marks the
    // previously-imported documents as unchanged.
    expect($first->importedCount())->toBe(4)
        ->and($first->unchangedCount())->toBe(0)
        ->and($second->importedCount())->toBe(0)
        ->and($second->unchangedCount())->toBe(4);
});

it('re-imports everything when forced', function () {
    $repository = new InMemoryDocsPageRepository;
    $importer = buildImporter($repository);

    $importer->import(fixturePath());
    $forced = $importer->import(fixturePath(), force: true);

    expect($forced->importedCount())->toBe(4)
        ->and($forced->unchangedCount())->toBe(0);
});

it('records the unknown document as skipped, not failed', function () {
    $report = buildImporter(new InMemoryDocsPageRepository)->import(fixturePath());

    expect($report->skipped())->toHaveKey('unknown.colophon');
});

it('records the malformed document as failed and keeps going', function () {
    $report = buildImporter(new InMemoryDocsPageRepository)->import(fixturePath());

    $failedPaths = array_keys($report->failed());

    expect($failedPaths)->toContain('broken/malformed.xml')
        ->and($report->importedCount())->toBe(4); // failure did not abort the run
});

it('normalizes content before persisting', function () {
    $repository = new InMemoryDocsPageRepository;

    buildImporter($repository)->import(fixturePath());
    $page = $repository->find('reference.array.array-map');

    expect($page)->not->toBeNull()
        ->and($page->content)->toBe('fake content');
});
