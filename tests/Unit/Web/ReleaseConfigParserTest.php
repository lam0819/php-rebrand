<?php

declare(strict_types=1);

use App\Web\Parsing\ReleaseConfigParser;

it('parses the $RELEASES block from version.inc', function () {
    $source = file_get_contents(base_path('tests/Fixtures/web-php/include/version.inc'));

    $releases = (new ReleaseConfigParser)->parse($source);

    expect($releases)->toHaveCount(4);

    $byBranch = collect($releases)->keyBy('branch');

    expect($byBranch['8.5']->version)->toBe('8.5.7')
        ->and($byBranch['8.5']->date)->toBe('04 Jun 2026')
        ->and($byBranch['8.5']->tags)->toBe([])
        ->and($byBranch['8.5']->sha256['tar.gz'])->toBe('e5eba93fd6dd3241d0e61e932eb99a3783b40568553fb0e511b660ecd863a049')
        ->and($byBranch['8.5']->sha256)->toHaveKeys(['tar.gz', 'tar.bz2', 'tar.xz']);

    expect($byBranch['8.3']->version)->toBe('8.3.31')
        ->and($byBranch['8.3']->tags)->toBe(['security']);
});

it('ignores the transform section after the authored config', function () {
    $source = file_get_contents(base_path('tests/Fixtures/web-php/include/version.inc'));

    $releases = (new ReleaseConfigParser)->parse($source);

    // Only the four authored $data[...] blocks, not the $ret loop variables.
    expect(collect($releases)->pluck('branch')->all())
        ->toBe(['8.5', '8.4', '8.3', '8.2']);
});
