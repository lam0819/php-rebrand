<?php

declare(strict_types=1);

use App\Docs\DTO\DocPageDTO;
use App\Docs\DTO\ExampleDTO;
use App\Docs\DTO\MetadataDTO;
use App\Docs\DTO\ParameterDTO;
use App\Docs\DTO\ReturnValueDTO;
use App\Docs\Enums\DocumentType;
use App\Docs\Persistence\EloquentDocsPageRepository;

function savePage(
    string $title,
    string $slug,
    DocumentType $type,
    string $sourcePath,
    ?string $purpose = null,
    ?string $signature = null,
    ?string $version = null,
    ?string $deprecated = null,
    ?string $removed = null,
): void {
    (new EloquentDocsPageRepository)->save(new DocPageDTO(
        docId: $slug,
        slug: $slug,
        title: $title,
        type: $type,
        sourcePath: $sourcePath,
        metadata: new MetadataDTO(
            purpose: $purpose,
            version: $version,
            extra: array_filter([
                'signature' => $signature,
                'deprecated' => $deprecated,
                'removed' => $removed,
            ], static fn (?string $value): bool => $value !== null),
        ),
        content: 'Intro paragraph.',
        parameters: [new ParameterDTO(name: 'string', type: 'string')],
        returnValue: new ReturnValueDTO(type: 'int'),
        examples: [new ExampleDTO(title: 'Basic', code: '<?php echo strlen("a");', language: 'php')],
        // Mirrors what the renderer produces at import: the full body as HTML.
        bodyHtml: '<h2 id="description">Description</h2>'
            .($signature !== null ? '<div class="sig">'.$signature.'</div>' : '')
            .'<p>'.($purpose ?? 'Intro paragraph.').'</p>',
    ));
}

it('server-renders a function page with its signature and SEO metadata', function () {
    savePage('strlen', 'reference-strings-functions-strlen', DocumentType::RefEntry,
        'reference/strings/functions/strlen.xml', 'Get string length', 'int strlen(string $string)');

    $this->get('/manual/reference-strings-functions-strlen')
        ->assertOk()
        ->assertSee('strlen', false)
        ->assertSee('int strlen(string $string)', false)
        ->assertSee('<title>strlen — PHP Manual</title>', false)
        ->assertSee('rel="canonical"', false)
        ->assertSee('TechArticle', false)
        ->assertSee('Get string length', false);
});

it('shows version availability and a deprecation badge from versions.xml data', function () {
    savePage('convert_cyr_string', 'reference-strings-functions-convert-cyr-string', DocumentType::RefEntry,
        'reference/strings/functions/convert-cyr-string.xml', 'Convert character set',
        version: 'PHP 4, PHP 5, PHP 7', deprecated: 'PHP 7.4.0', removed: 'PHP 8');

    $this->get('/manual/reference-strings-functions-convert-cyr-string')
        ->assertOk()
        ->assertSee('(PHP 4, PHP 5, PHP 7)', false)
        ->assertSee('Removed in PHP 8', false);
});

it('returns 404 for an unknown slug', function () {
    $this->get('/manual/does-not-exist')->assertNotFound();
});

it('301-redirects a short function name to its canonical page', function () {
    savePage('strlen', 'reference-strings-functions-strlen', DocumentType::RefEntry,
        'reference/strings/functions/strlen.xml', 'Get string length', 'int strlen(string $string)');

    $this->get('/manual/strlen')
        ->assertRedirect('/manual/reference-strings-functions-strlen')
        ->assertStatus(301);
});

it('prefers the core function over an extension variant for an alias', function () {
    savePage('printf', 'reference-strings-functions-printf', DocumentType::RefEntry, 'reference/strings/functions/printf.xml');
    savePage('printf', 'reference-someext-much-deeper-functions-printf', DocumentType::RefEntry, 'reference/someext/much/deeper/functions/printf.xml');

    $this->get('/manual/printf')->assertRedirect('/manual/reference-strings-functions-printf');
});

it('keeps the current page in its sidebar, marked active', function () {
    savePage('Attributes', 'language-attributes', DocumentType::Chapter, 'language/attributes.xml', 'PHP attributes');
    savePage('Constants', 'language-constants', DocumentType::Chapter, 'language/constants.xml', 'PHP constants');

    $this->get('/manual/language-attributes')
        ->assertOk()
        // The active page is highlighted, not dropped from the sidebar.
        ->assertSee('class="side-link active" href="/manual/language-attributes"', false)
        ->assertSee('href="/manual/language-constants"', false);
});

it('lists child pages of an index page in an "In this section" grid', function () {
    savePage('Types', 'language-types', DocumentType::Chapter, 'language/types.xml', 'PHP type system');
    savePage('Booleans', 'language-types-boolean', DocumentType::Chapter, 'language/types/boolean.xml', 'The bool type');
    savePage('Integers', 'language-types-integer', DocumentType::Chapter, 'language/types/integer.xml', 'The int type');

    $this->get('/manual/language-types')
        ->assertOk()
        ->assertSee('In this section', false)
        ->assertSee('/manual/language-types-boolean', false)
        ->assertSee('/manual/language-types-integer', false);
});
