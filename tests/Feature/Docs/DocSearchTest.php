<?php

declare(strict_types=1);

use App\Docs\DTO\DocPageDTO;
use App\Docs\DTO\MetadataDTO;
use App\Docs\Enums\DocumentType;
use App\Docs\Persistence\EloquentDocsPageRepository;
use App\Docs\Search\DocSearch;

function indexPage(string $title, string $slug, ?string $purpose = null): void
{
    (new EloquentDocsPageRepository)->save(new DocPageDTO(
        docId: $slug,
        slug: $slug,
        title: $title,
        type: DocumentType::RefEntry,
        sourcePath: "{$slug}.xml",
        metadata: new MetadataDTO(purpose: $purpose),
    ));
}

it('builds an FTS index and ranks the exact title match first', function () {
    indexPage('strlen', 'reference-strings-functions-strlen', 'Get string length');
    indexPage('mb_strlen', 'reference-mbstring-functions-mb-strlen', 'Get string length (multibyte)');

    $search = app(DocSearch::class);
    expect($search->available())->toBeTrue();
    expect($search->rebuild())->toBe(2);

    $results = $search->summaries('strlen', 10);

    expect($results)->not->toBeNull()
        ->and($results[0]['name'])->toBe('strlen')
        ->and($results[0]['slug'])->toBe('reference-strings-functions-strlen');
});

it('treats underscores as token separators so str_replace matches', function () {
    indexPage('str_replace', 'reference-strings-functions-str-replace', 'Replace substrings');
    app(DocSearch::class)->rebuild();

    $results = app(DocSearch::class)->summaries('str_replace');

    expect($results)->not->toBeNull()
        ->and(collect($results)->pluck('name'))->toContain('str_replace');
});

it('returns an empty list (not null) when nothing matches', function () {
    indexPage('array_map', 'reference-array-array-map');
    app(DocSearch::class)->rebuild();

    expect(app(DocSearch::class)->summaries('zzzznomatch'))->toBe([]);
});

it('counts matches and constrains by type', function () {
    indexPage('strlen', 'reference-strings-functions-strlen', 'Get string length');
    indexPage('mb_strlen', 'reference-mbstring-functions-mb-strlen', 'Multibyte string length');
    (new EloquentDocsPageRepository)->save(new DocPageDTO(
        docId: 'language-strings', slug: 'language-strings', title: 'Strings chapter',
        type: DocumentType::Chapter, sourcePath: 'language/strings.xml',
        metadata: new MetadataDTO(purpose: 'About strings'),
    ));
    app(DocSearch::class)->rebuild();

    $search = app(DocSearch::class);

    expect($search->count('strlen'))->toBe(2)
        ->and($search->count('string', 'refentry'))->toBe(2)
        ->and($search->count('string', 'chapter'))->toBe(1);
});
