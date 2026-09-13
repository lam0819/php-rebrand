<?php

declare(strict_types=1);

use App\Docs\DTO\DocPageDTO;
use App\Docs\DTO\MetadataDTO;
use App\Docs\Enums\DocumentType;
use App\Docs\Persistence\EloquentDocsPageRepository;

beforeEach(function () {
    (new EloquentDocsPageRepository)->save(new DocPageDTO(
        docId: 'reference-strings-functions-str-replace',
        slug: 'reference-strings-functions-str-replace',
        title: 'str_replace',
        type: DocumentType::RefEntry,
        sourcePath: 'reference/strings/functions/str-replace.xml',
        metadata: new MetadataDTO,
        content: 'body',
    ));
});

it('serves an xml sitemap listing static and imported pages', function () {
    $res = $this->get('/sitemap.xml');

    $res->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    expect($res->getContent())
        ->toContain('<urlset')
        ->toContain('/docs')
        ->toContain('/manual/reference-strings-functions-str-replace');

    // 6 static pages + 1 imported page = 7 <loc> entries.
    expect(substr_count((string) $res->getContent(), '<loc>'))->toBe(7);
});

it('serves robots.txt pointing at the sitemap', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Sitemap:')
        ->assertSee('/sitemap.xml');
});
