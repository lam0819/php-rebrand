<?php

declare(strict_types=1);

use App\Docs\DTO\DocPageDTO;
use App\Docs\DTO\MetadataDTO;
use App\Docs\Enums\DocumentType;
use App\Docs\Persistence\EloquentDocsPageRepository;
use Illuminate\Support\Facades\File;

function searchExportPage(string $title, string $slug, ?string $purpose = null, string $content = ''): void
{
    (new EloquentDocsPageRepository)->save(new DocPageDTO(
        docId: $slug,
        slug: $slug,
        title: $title,
        type: DocumentType::RefEntry,
        sourcePath: "{$slug}.xml",
        metadata: new MetadataDTO(purpose: $purpose),
        content: $content,
    ));
}

it('exports pages as newline-delimited json for the index builder', function () {
    searchExportPage('strlen', 'reference-strings-strlen', 'Get string length', "Returns the length\nof a string.");
    searchExportPage('array_map', 'reference-array-array-map', 'Apply a callback');

    $path = storage_path('app/testing-search-export.ndjson');
    File::delete($path);

    $this->artisan('search:export', ['--path' => $path])->assertSuccessful();

    $lines = array_values(array_filter(explode("\n", (string) file_get_contents($path))));
    expect($lines)->toHaveCount(2);

    $rows = array_map(static fn (string $line): array => json_decode($line, true, flags: JSON_THROW_ON_ERROR), $lines);

    expect($rows[0]['slug'])->toBe('reference-strings-strlen')
        ->and($rows[0]['title'])->toBe('strlen')
        ->and($rows[0]['type'])->toBe('refentry')
        ->and($rows[0]['purpose'])->toBe('Get string length')
        // whitespace is normalised to a single line
        ->and($rows[0]['excerpt'])->toBe('Returns the length of a string.');

    File::delete($path);
});

it('truncates the excerpt to the configured length', function () {
    config(['search.index.excerpt_chars' => 10]);
    searchExportPage('long', 'reference-long', null, str_repeat('a', 100));

    $path = storage_path('app/testing-search-export-limit.ndjson');
    File::delete($path);

    $this->artisan('search:export', ['--path' => $path])->assertSuccessful();

    $row = json_decode(trim((string) file_get_contents($path)), true, flags: JSON_THROW_ON_ERROR);
    expect($row['excerpt'])->toBe(str_repeat('a', 10));

    File::delete($path);
});

it('serves the gzipped search index with Content-Encoding: gzip', function () {
    $gz = storage_path('app/testing-manual-search.inlay.gz');
    File::put($gz, gzencode('inlay-bytes'));
    config(['search.index.gz_path' => $gz, 'search.index.path' => $gz.'.missing']);

    $response = $this->get('/manual-search.inlay');

    $response->assertOk()
        ->assertHeader('Content-Encoding', 'gzip');

    File::delete($gz);
});

it('returns 404 when no search index has been installed', function () {
    config([
        'search.index.gz_path' => storage_path('app/does-not-exist.inlay.gz'),
        'search.index.path' => storage_path('app/does-not-exist.inlay'),
    ]);

    $this->get('/manual-search.inlay')->assertNotFound();
});
