<?php

declare(strict_types=1);

use App\Docs\Persistence\DocPage;
use App\Docs\Search\DocSearch;

it('builds a populated, searchable database from the source tree', function () {
    // Point the importer at the test fixtures instead of the real clone.
    config(['docs.source.path' => fixturePath()]);

    $this->artisan('docs:build --skip-fetch')->assertSuccessful();

    // The 4 supported fixtures are imported and indexed for search.
    expect(DocPage::query()->count())->toBe(4);

    $results = app(DocSearch::class)->summaries('array');
    expect($results)->not->toBeNull()
        ->and(collect($results)->pluck('slug'))->toContain('reference-array-array-map');
});
