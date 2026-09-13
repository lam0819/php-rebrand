<?php

use App\Http\Controllers\DocsController;
use Illuminate\Support\Facades\Route;

Route::get('/docs', [DocsController::class, 'index']);
Route::get('/docs/search', [DocsController::class, 'search']);
Route::get('/docs/{slug}', [DocsController::class, 'show']);
