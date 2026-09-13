<?php

declare(strict_types=1);

use App\Web\Models\NewsItem;
use App\Web\Models\PhpRelease;

it('renders the home page with releases and news from the database', function () {
    PhpRelease::factory()->create(['branch' => '8.5', 'version' => '8.5.7']);
    PhpRelease::factory()->create(['branch' => '8.4', 'version' => '8.4.22']);
    NewsItem::factory()->create([
        'entry_id' => '2026-06-04-1',
        'title' => 'PHP 8.5.7 Released!',
        'published_at' => now()->subDay(),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('8.5.7')
        ->assertSee('Download 8.5.7')
        ->assertSee('PHP 8.5.7 Released!');
});

it('lists news newest-first and paginates', function () {
    NewsItem::factory()->count(25)->sequence(fn ($s) => [
        'entry_id' => '2026-01-'.str_pad((string) ($s->index + 1), 2, '0', STR_PAD_LEFT).'-1',
        'published_at' => now()->subDays($s->index),
    ])->create();

    $this->get('/news')
        ->assertOk()
        ->assertSee('All announcements')
        ->assertSee('Older');
});

it('shows a single news entry page', function () {
    NewsItem::factory()->create([
        'entry_id' => '2026-06-04-1',
        'title' => 'PHP 8.5.7 Released!',
        'body_html' => '<p>The PHP team announces 8.5.7.</p>',
    ]);

    $this->get('/news/2026-06-04-1')
        ->assertOk()
        ->assertSee('PHP 8.5.7 Released!')
        ->assertSee('The PHP team announces 8.5.7.', false);
});

it('returns 404 for an unknown news entry', function () {
    $this->get('/news/does-not-exist')->assertNotFound();
});

it('renders the downloads page with real checksums', function () {
    PhpRelease::factory()->create([
        'branch' => '8.5',
        'version' => '8.5.7',
        'sha256' => ['tar.gz' => str_repeat('a', 64), 'tar.xz' => str_repeat('b', 64)],
    ]);

    $this->get('/downloads')
        ->assertOk()
        ->assertSee('8.5.7')
        ->assertSee(str_repeat('a', 64))
        ->assertSee('distributions/php-8.5.7.tar.gz');
});
