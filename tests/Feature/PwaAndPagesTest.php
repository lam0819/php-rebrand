<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serves a valid PWA manifest', function () {
    $this->get('/manifest.webmanifest')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/manifest+json')
        ->assertJsonPath('name', 'PHP Manual')
        ->assertJsonPath('display', 'standalone')
        ->assertJsonPath('start_url', '/');
});

it('serves a service worker that caches and handles fetch', function () {
    $res = $this->get('/sw.js')->assertOk();

    expect($res->headers->get('Content-Type'))->toContain('javascript');
    expect($res->getContent())
        ->toContain("addEventListener('install'")
        ->toContain("addEventListener('fetch'");
});

it('renders the marketing and docs pages as server HTML', function (string $path, string $needle) {
    $this->get($path)->assertOk()->assertSee($needle, false);
})->with([
    'home' => ['/', 'The pragmatic language the web runs on.'],
    'docs' => ['/docs', 'Browse the reference'],
    'downloads' => ['/downloads', 'Get PHP'],
    'news' => ['/news', 'All announcements'],
    'get involved' => ['/get-involved', 'Get involved'],
    'offline' => ['/offline', "You're offline."],
]);

it('links every page header to the live search island', function () {
    $this->get('/')->assertOk()->assertSee('data-search', false);
});

it('has no dead curated links in the footer', function () {
    $html = $this->get('/')->assertOk()->getContent();

    // The language-reference index has no standalone page; it must point at a
    // real chapter, never the old placeholder slug.
    expect($html)
        ->not->toContain('/manual/langref')
        ->toContain('/manual/language-basic-syntax');

    // Topic links that have no dedicated in-app page go to real php.net resources.
    expect($html)
        ->toContain('https://www.php.net/supported-versions.php')
        ->toContain('https://thephp.foundation/')
        ->toContain('https://github.com/php/doc-en');
});
