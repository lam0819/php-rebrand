<?php

declare(strict_types=1);

use App\Docs\Importer\RecursiveXmlFileFinder;
use App\Docs\Support\SourceFile;

it('finds every xml file recursively and ignores non-xml files', function () {
    $files = iterator_to_array((new RecursiveXmlFileFinder)->find(fixturePath()));

    expect($files)->each->toBeInstanceOf(SourceFile::class);

    $relatives = array_map(fn (SourceFile $f) => $f->relativePath, $files);

    expect($relatives)
        ->toContain('reference/array/array-map.xml')
        ->toContain('language/control-structures.xml')
        ->toContain('appendix/migration85.xml')
        ->and(array_filter($relatives, fn (string $r) => ! str_ends_with($r, '.xml')))->toBe([]);
});

it('derives a stable doc id and slug from the relative path', function () {
    $file = new SourceFile('/abs/reference/array/array-map.xml', 'reference/array/array-map.xml');

    expect($file->docId())->toBe('reference.array.array-map')
        ->and($file->slug())->toBe('reference-array-array-map');
});

it('yields nothing for a directory that does not exist', function () {
    $files = iterator_to_array((new RecursiveXmlFileFinder)->find('/no/such/path'));

    expect($files)->toBe([]);
});
