<?php

declare(strict_types=1);

use App\Docs\DTO\DocPageDTO;
use App\Docs\DTO\MetadataDTO;
use App\Docs\Enums\DocumentType;
use App\Docs\Importer\DocumentParserRegistry;
use App\Docs\Importer\DomXmlDocumentLoader;
use App\Docs\Importer\RecursiveXmlFileFinder;
use App\Docs\Importer\RootElementDocumentClassifier;
use App\Docs\Normalizer\DefaultContentNormalizer;
use App\Docs\Persistence\DocPage;
use App\Docs\Persistence\EloquentDocsPageRepository;
use App\Docs\Services\ImporterService;
use App\Docs\Testing\FakeDocumentParser;

function page(string $docId = 'reference.array.array-map', string $content = 'body'): DocPageDTO
{
    return new DocPageDTO(
        docId: $docId,
        slug: 'reference-array-array-map',
        title: 'array_map',
        type: DocumentType::RefEntry,
        sourcePath: 'reference/array/array-map.xml',
        metadata: new MetadataDTO(purpose: 'Applies the callback', version: '8.5'),
        rawXml: '<refentry/>',
        content: $content,
    );
}

it('persists a document page and stores structured metadata as json', function () {
    (new EloquentDocsPageRepository)->save(page());

    $row = DocPage::query()->where('doc_id', 'reference.array.array-map')->firstOrFail();

    expect($row->title)->toBe('array_map')
        ->and($row->type)->toBe('refentry')
        ->and($row->metadata)->toBeArray()
        ->and($row->metadata['metadata']['purpose'])->toBe('Applies the callback');

    $this->assertDatabaseHas('docs_pages', ['doc_id' => 'reference.array.array-map', 'slug' => 'reference-array-array-map']);
});

it('upserts on doc id rather than duplicating', function () {
    $repository = new EloquentDocsPageRepository;

    $repository->save(page(content: 'first'));
    $repository->save(page(content: 'second'));

    expect(DocPage::query()->count())->toBe(1)
        ->and(DocPage::query()->firstOrFail()->content)->toBe('second')
        ->and($repository->existsForDocId('reference.array.array-map'))->toBeTrue();
});

it('runs the full pipeline into the database', function () {
    $importer = new ImporterService(
        finder: new RecursiveXmlFileFinder,
        loader: new DomXmlDocumentLoader,
        classifier: new RootElementDocumentClassifier,
        parsers: new DocumentParserRegistry([
            new FakeDocumentParser([
                DocumentType::RefEntry,
                DocumentType::Chapter,
                DocumentType::Article,
                DocumentType::Appendix,
            ]),
        ]),
        normalizer: new DefaultContentNormalizer,
        repository: new EloquentDocsPageRepository,
    );

    $report = $importer->import(fixturePath());

    expect($report->importedCount())->toBe(4)
        ->and(DocPage::query()->count())->toBe(4);
});
