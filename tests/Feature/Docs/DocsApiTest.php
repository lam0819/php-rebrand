<?php

declare(strict_types=1);

use App\Docs\DTO\DocPageDTO;
use App\Docs\DTO\MetadataDTO;
use App\Docs\DTO\ParameterDTO;
use App\Docs\Enums\DocumentType;
use App\Docs\Persistence\EloquentDocsPageRepository;

function seedPage(string $title, string $slug, DocumentType $type, ?string $purpose = null): void
{
    (new EloquentDocsPageRepository)->save(new DocPageDTO(
        docId: $slug,
        slug: $slug,
        title: $title,
        type: $type,
        sourcePath: "{$slug}.xml",
        metadata: new MetadataDTO(purpose: $purpose, extra: ['signature' => "array {$title}(...)"]),
        content: 'body',
        parameters: [new ParameterDTO(name: 'callback', type: 'callable')],
    ));
}

beforeEach(function () {
    seedPage('array_map', 'reference-array-array-map', DocumentType::RefEntry, 'Applies the callback');
    seedPage('Enumerations', 'language-enumerations', DocumentType::Chapter, 'Backed and pure enums');
    // Search reads the FTS index, which the import pipeline rebuilds after writing.
    app(App\Docs\Search\DocSearch::class)->rebuild();
});

it('lists imported pages with totals and type counts', function () {
    $this->getJson('/api/docs')
        ->assertOk()
        ->assertJsonPath('total', 2)
        ->assertJsonPath('counts.refentry', 1)
        ->assertJsonPath('counts.chapter', 1)
        ->assertJsonStructure(['total', 'counts', 'page', 'perPage', 'items']);
});

it('filters the index by query', function () {
    $this->getJson('/api/docs?q=array')
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('items.0.name', 'array_map');
});

it('filters the index by type', function () {
    $this->getJson('/api/docs?type=chapter')
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('items.0.name', 'Enumerations');
});

it('searches imported pages by name', function () {
    $this->getJson('/api/docs/search?q=array')
        ->assertOk()
        ->assertJsonPath('results.0.name', 'array_map')
        ->assertJsonPath('results.0.slug', 'reference-array-array-map');
});

it('returns an empty result set for a blank query', function () {
    $this->getJson('/api/docs/search?q=')
        ->assertOk()
        ->assertJsonPath('results', []);
});

it('shows a single page with its signature and parameters', function () {
    $this->getJson('/api/docs/reference-array-array-map')
        ->assertOk()
        ->assertJsonPath('title', 'array_map')
        ->assertJsonPath('signature', 'array array_map(...)')
        ->assertJsonPath('parameters.0.name', 'callback');
});

it('404s for an unknown slug', function () {
    $this->getJson('/api/docs/does-not-exist')->assertNotFound();
});
