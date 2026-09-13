<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keep the published manual in step with upstream php/doc-en. Incremental, so a
// run with no upstream changes is a cheap no-op.
Schedule::command('docs:sync')
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->runInBackground();

// Keep news and release data in step with upstream php/web-php. Incremental, so
// a run with no upstream changes is a cheap no-op.
Schedule::command('web:sync')
    ->dailyAt('04:30')
    ->withoutOverlapping()
    ->runInBackground();
