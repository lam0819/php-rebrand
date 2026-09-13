<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the privacy and cookies page', function () {
    $this->get('/privacy')
        ->assertOk()
        ->assertSee('Privacy & cookies')
        ->assertSee('essential', escape: false);
});

it('shows the cookie consent banner on a page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('data-cookie-consent', escape: false);
});
