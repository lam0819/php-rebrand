<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Response;

/**
 * Streams the prebuilt InlaySQL search index. The artifact is stored gzipped
 * and served with `Content-Encoding: gzip`, so the browser transparently
 * decompresses it into the bytes InlaySQL's WASM module opens.
 */
final class SearchIndexController extends Controller
{
    public function __invoke(): Response
    {
        $gzPath = config()->string('search.index.gz_path');
        $plainPath = config()->string('search.index.path');

        if (is_file($gzPath)) {
            return response()->file($gzPath, [
                'Content-Type' => 'application/octet-stream',
                'Content-Encoding' => 'gzip',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'public, max-age=3600',
            ]);
        }

        if (is_file($plainPath)) {
            return response()->file($plainPath, [
                'Content-Type' => 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'public, max-age=3600',
            ]);
        }

        return response()->json(['error' => 'Search index not available.'], 404);
    }
}
