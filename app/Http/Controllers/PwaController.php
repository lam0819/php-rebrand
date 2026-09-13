<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Serves the PWA manifest and service worker. Both are generated (not static
 * files) so they can reference app config / Vite assets and carry correct
 * cache headers. The service worker uses a stale-while-revalidate strategy for
 * pages and assets so visited pages load instantly and work offline.
 */
final class PwaController extends Controller
{
    public function manifest(): Response
    {
        $manifest = [
            'name' => 'PHP Manual',
            'short_name' => 'PHP',
            'description' => 'The PHP manual — fast, searchable, offline-ready.',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#0f0f1a',
            'theme_color' => '#574fd6',
            'icons' => [
                [
                    'src' => $this->icon(192),
                    'sizes' => '192x192',
                    'type' => 'image/svg+xml',
                    'purpose' => 'any maskable',
                ],
                [
                    'src' => $this->icon(512),
                    'sizes' => '512x512',
                    'type' => 'image/svg+xml',
                    'purpose' => 'any maskable',
                ],
            ],
        ];

        return response($this->json($manifest), 200)
            ->header('Content-Type', 'application/manifest+json');
    }

    public function serviceWorker(): Response
    {
        $configured = config('app.docs_sw_version', 'v1');
        $version = is_string($configured) ? $configured : 'v1';

        $js = <<<JS
        // PHP Manual service worker — cache app shell + visited pages.
        const CACHE = 'php-manual-{$version}';
        const PRECACHE = ['/', '/docs', '/offline'];

        self.addEventListener('install', (e) => {
          self.skipWaiting();
          e.waitUntil(caches.open(CACHE).then((c) => c.addAll(PRECACHE).catch(() => {})));
        });

        self.addEventListener('activate', (e) => {
          e.waitUntil(
            caches.keys().then((keys) =>
              Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
            ).then(() => self.clients.claim())
          );
        });

        self.addEventListener('fetch', (e) => {
          const req = e.request;
          if (req.method !== 'GET') return;
          const url = new URL(req.url);
          if (url.origin !== self.location.origin) return;
          // Never cache Livewire update calls or other POSTs.
          if (url.pathname.startsWith('/livewire')) return;

          // Stale-while-revalidate: serve cache instantly, refresh in background.
          e.respondWith(
            caches.open(CACHE).then((cache) =>
              cache.match(req).then((cached) => {
                const network = fetch(req)
                  .then((res) => {
                    if (res.ok && (res.type === 'basic' || res.type === 'default')) {
                      cache.put(req, res.clone());
                    }
                    return res;
                  })
                  .catch(() => cached || caches.match('/offline'));
                return cached || network;
              })
            )
          );
        });
        JS;

        return response($js, 200)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'no-cache');
    }

    private function icon(int $size): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" width="'.$size.'" height="'.$size.'">'
            .'<rect width="32" height="32" rx="7" fill="#574fd6"/>'
            .'<g fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">'
            .'<path d="M9.5 24v-7a7 7 0 0 1 14 0v7"/>'
            .'<path d="M9.5 17.5c-2.7.2-4.2-1.6-4-4.2.2-2.6 2.4-3.2 4-2"/>'
            .'<path d="M20 24c0 3 .5 5 2.6 5.5 1.9.4 3-1 2.5-2.7"/>'
            .'<path d="M11 24v2.4M15.5 24v2.4"/></g>'
            .'<circle cx="13.5" cy="15" r="1.2" fill="#fff"/></svg>';

        return 'data:image/svg+xml,'.rawurlencode($svg);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function json(array $data): string
    {
        return (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
