<?php

declare(strict_types=1);

use App\Docs\DTO\DocPageDTO;
use App\Docs\DTO\MetadataDTO;
use App\Docs\Enums\DocumentType;
use App\Docs\Persistence\EloquentDocsPageRepository;

function seedDoc(string $slug, string $title, string $purpose, string $bodyHtml): void
{
    (new EloquentDocsPageRepository)->save(new DocPageDTO(
        docId: $slug,
        slug: $slug,
        title: $title,
        type: DocumentType::RefEntry,
        sourcePath: "reference/{$slug}.xml",
        metadata: new MetadataDTO(purpose: $purpose),
        bodyHtml: $bodyHtml,
    ));
}

it('serves an llms.txt index in Markdown', function () {
    $this->get('/llms.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertSee('# PHP Manual', false)
        ->assertSee('## Language reference', false)
        ->assertSee('/manual/language-fibers.md', false);
});

it('renders a manual page as Markdown', function () {
    seedDoc('reference-strings-functions-strlen', 'strlen', 'Get string length',
        '<h2 id="description">Description</h2><div class="sig">int strlen(string $string)</div>'
        .'<p>Returns the length of the given <code class="inline">string</code>.</p>'
        .'<div class="code"><div class="code-head"><span class="fname">php</span></div><pre><code>echo strlen("a");</code></pre></div>');

    $res = $this->get('/manual/reference-strings-functions-strlen.md')->assertOk();

    expect($res->headers->get('Content-Type'))->toContain('text/markdown');
    expect($res->getContent())
        ->toContain('# strlen')
        ->toContain('> Get string length')
        ->toContain('## Description')
        ->toContain('`int strlen(string $string)`')
        ->toContain('```php')
        ->toContain('Returns the length of the given `string`');
});

it('keeps the HTML manual page working alongside the .md route', function () {
    seedDoc('language-fibers', 'Fibers', 'Interruptible functions', '<h2 id="overview">Overview</h2><p>Body.</p>');

    $this->get('/manual/language-fibers')->assertOk()->assertSee('<!doctype html>', false);
    $this->get('/manual/language-fibers.md')->assertOk()->assertSee('# Fibers', false);
});

it('returns 404 for an unknown markdown page', function () {
    $this->get('/manual/does-not-exist.md')->assertNotFound();
});
