<?php

declare(strict_types=1);

use App\Docs\Versions\VersionCatalog;

beforeEach(function () {
    $this->dir = sys_get_temp_dir().'/version-catalog-'.bin2hex(random_bytes(4));
    mkdir($this->dir.'/reference/strings', 0777, true);
    mkdir($this->dir.'/language/control-structures', 0777, true);

    file_put_contents($this->dir.'/reference/strings/versions.xml', <<<'XML'
        <?xml version="1.0" encoding="utf-8"?>
        <versions>
         <function name="strlen" from="PHP 4, PHP 5, PHP 7, PHP 8"/>
         <function name="convert_cyr_string" from="PHP 4, PHP 5, PHP 7" deprecated="PHP 7.4.0" removed="PHP 8"/>
        </versions>
        XML);

    file_put_contents($this->dir.'/language/control-structures/versions.xml', <<<'XML'
        <?xml version="1.0" encoding="utf-8"?>
        <versions>
         <function name="break" from="PHP 4, PHP 5, PHP 7, PHP 8"/>
         <function name="match" from="PHP 8"/>
        </versions>
        XML);
});

afterEach(function () {
    array_map('unlink', glob($this->dir.'/*/*/versions.xml') ?: []);
});

it('resolves version availability across distributed versions.xml files', function () {
    $catalog = new VersionCatalog($this->dir);

    expect($catalog->lookup('strlen')?->from)->toBe('PHP 4, PHP 5, PHP 7, PHP 8');
    expect($catalog->lookup('break')?->from)->toBe('PHP 4, PHP 5, PHP 7, PHP 8');
    expect($catalog->lookup('match')?->from)->toBe('PHP 8');
});

it('matches names case-insensitively', function () {
    $catalog = new VersionCatalog($this->dir);

    expect($catalog->lookup('STRLEN')?->from)->toBe('PHP 4, PHP 5, PHP 7, PHP 8');
});

it('captures deprecated and removed versions', function () {
    $info = (new VersionCatalog($this->dir))->lookup('convert_cyr_string');

    expect($info?->deprecated)->toBe('PHP 7.4.0')
        ->and($info?->removed)->toBe('PHP 8');
});

it('returns null for unknown symbols or a missing source directory', function () {
    expect((new VersionCatalog($this->dir))->lookup('does_not_exist'))->toBeNull();
    expect((new VersionCatalog('/no/such/path'))->lookup('strlen'))->toBeNull();
});
