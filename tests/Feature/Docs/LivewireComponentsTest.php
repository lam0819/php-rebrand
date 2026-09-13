<?php

declare(strict_types=1);

use App\Docs\DTO\DocPageDTO;
use App\Docs\DTO\MetadataDTO;
use App\Docs\Enums\DocumentType;
use App\Docs\Persistence\EloquentDocsPageRepository;
use App\Docs\Search\DocSearch;
use Livewire\Livewire;

function makePage(string $title, string $slug, DocumentType $type, ?string $purpose = null): void
{
    (new EloquentDocsPageRepository)->save(new DocPageDTO(
        docId: $slug,
        slug: $slug,
        title: $title,
        type: $type,
        sourcePath: "{$slug}.xml",
        metadata: new MetadataDTO(purpose: $purpose),
    ));
}

it('search island shows ranked live results once the query is typed', function () {
    makePage('strlen', 'reference-strings-functions-strlen', DocumentType::RefEntry, 'Get string length');
    makePage('mb_strlen', 'reference-mbstring-functions-mb-strlen', DocumentType::RefEntry, 'Multibyte length');
    app(DocSearch::class)->rebuild();

    Livewire::test('search')
        ->assertDontSee('reference-strings-functions-strlen')
        ->set('query', 'strlen')
        ->assertSee('strlen')
        ->assertSeeHtml('/manual/reference-strings-functions-strlen');
});

it('search island stays quiet for a single character', function () {
    makePage('array_map', 'reference-array-array-map', DocumentType::RefEntry);
    app(DocSearch::class)->rebuild();

    Livewire::test('search')
        ->set('query', 'a')
        ->assertSet('query', 'a')
        ->assertDontSeeHtml('/manual/reference-array-array-map');
});

it('docs browser lists pages and filters by type', function () {
    makePage('array_map', 'reference-array-array-map', DocumentType::RefEntry, 'Map a callback');
    makePage('Enumerations', 'language-enumerations', DocumentType::Chapter, 'Backed enums');

    Livewire::test('docs')
        ->assertSee('array_map')
        ->assertSee('Enumerations')
        ->set('type', 'chapter')
        ->assertSee('Enumerations')
        ->assertDontSee('array_map');
});

it('docs browser category chip drives a ranked FTS query', function () {
    makePage('array_map', 'reference-array-array-map', DocumentType::RefEntry, 'Apply a callback to array elements');
    makePage('strlen', 'reference-strings-functions-strlen', DocumentType::RefEntry, 'Get string length');
    app(DocSearch::class)->rebuild();

    Livewire::test('docs')
        ->call('filterCategory', 'array')
        ->assertSet('query', 'array')
        ->assertSee('array_map')
        ->assertDontSee('reference-strings-functions-strlen');
});

it('docs browser text search routes through FTS and filters by type', function () {
    makePage('preg_match', 'reference-pcre-functions-preg-match', DocumentType::RefEntry, 'Perform a regex match');
    makePage('preg_replace', 'reference-pcre-functions-preg-replace', DocumentType::RefEntry, 'Perform a regex replace');
    makePage('PCRE patterns', 'reference-pcre-pattern', DocumentType::Chapter, 'Regex pattern syntax');
    app(DocSearch::class)->rebuild();

    Livewire::test('docs')
        ->set('query', 'preg')
        ->assertSee('preg_match')
        ->assertSee('preg_replace')
        ->set('type', 'refentry')
        ->assertSee('preg_match')
        ->assertDontSee('reference-pcre-pattern');
});
