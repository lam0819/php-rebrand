<?php

use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\DownloadsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LlmsController;
use App\Http\Controllers\ManualController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\SearchIndexController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
| Server-rendered site (Livewire v4 + Blade). Every page returns crawlable
| HTML; Livewire adds interactivity (live search, browse filters) on top.
*/

Route::get('/', HomeController::class)->name('home');
Route::view('/docs', 'docs.index')->name('docs');

// AI-agent friendly: llms.txt index + Markdown per page (before the HTML page).
Route::get('/llms.txt', [LlmsController::class, 'index']);
Route::get('/manual/{slug}.md', [LlmsController::class, 'page'])->name('manual.markdown');
Route::get('/manual/{slug}', [ManualController::class, 'show'])->name('manual.show');

Route::get('/downloads', DownloadsController::class)->name('downloads');
Route::get('/news', [NewsController::class, 'index'])->name('news');
Route::get('/news/{entry}', [NewsController::class, 'show'])->name('news.show');
Route::get('/changelog', [ChangelogController::class, 'index'])->name('changelog');
Route::get('/changelog/{version}', [ChangelogController::class, 'show'])->name('changelog.show')->where('version', '[0-9]+\.[0-9]+\.[0-9]+[a-zA-Z0-9.-]*');
Route::view('/get-involved', 'pages.get-involved')->name('get-involved');
Route::view('/privacy', 'pages.privacy')->name('privacy');
Route::view('/offline', 'offline')->name('offline');

// Prebuilt browser search index (InlaySQL vector + BM25).
Route::get('/manual-search.inlay', SearchIndexController::class)->name('search.index');

// PWA + crawler files.
Route::get('/manifest.webmanifest', [PwaController::class, 'manifest']);
Route::get('/sw.js', [PwaController::class, 'serviceWorker']);
Route::get('sitemap.xml', [SitemapController::class, 'index']);
Route::get('robots.txt', [SitemapController::class, 'robots']);
