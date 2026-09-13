<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Docs\Persistence\DocPage;
use App\Web\Models\ChangelogRelease;
use App\Web\Models\NewsItem;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * Generates an XML sitemap covering the marketing pages and every imported
 * documentation page, so the full manual is crawlable by search engines.
 */
final class SitemapController extends Controller
{
    /** Static pages and their priority. */
    private const STATIC_PAGES = [
        '/' => '1.0',
        '/docs' => '0.9',
        '/downloads' => '0.7',
        '/news' => '0.6',
        '/changelog' => '0.6',
        '/get-involved' => '0.6',
    ];

    public function index(): Response
    {
        $now = Carbon::now()->toAtomString();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach (self::STATIC_PAGES as $path => $priority) {
            $xml .= $this->url(url($path), $now, $priority);
        }

        NewsItem::query()
            ->select(['entry_id', 'published_at'])
            ->orderByDesc('published_at')
            ->each(function (NewsItem $item) use (&$xml): void {
                $xml .= $this->url(url('/news/'.$item->entry_id), $item->published_at->toAtomString(), '0.5');
            });

        ChangelogRelease::query()
            ->where('released', true)
            ->orderByDesc('released_on')
            ->each(function (ChangelogRelease $release) use (&$xml): void {
                $lastmod = $release->released_on?->toAtomString();
                $xml .= $this->url(url('/changelog/'.$release->version), $lastmod, '0.5');
            });

        DocPage::query()
            ->select(['id', 'slug', 'updated_at'])
            ->orderBy('id')
            ->chunkById(2000, function ($pages) use (&$xml): void {
                foreach ($pages as $page) {
                    $lastmod = $page->updated_at instanceof Carbon ? $page->updated_at->toAtomString() : null;
                    $xml .= $this->url(url('/manual/'.$page->slug), $lastmod, '0.8');
                }
            });

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $body = "User-agent: *\nAllow: /\n\nSitemap: ".url('/sitemap.xml')."\n";

        return response($body, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    private function url(string $loc, ?string $lastmod, string $priority): string
    {
        $entry = '  <url><loc>'.htmlspecialchars($loc, ENT_XML1).'</loc>';

        if ($lastmod !== null) {
            $entry .= '<lastmod>'.$lastmod.'</lastmod>';
        }

        return $entry.'<priority>'.$priority.'</priority></url>'."\n";
    }
}
