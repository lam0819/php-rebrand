<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Docs\Persistence\DocPage;
use App\Docs\Support\DocPagePresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Server-renders a single manual page as crawlable HTML (the SEO-critical path).
 * Content comes straight from the `docs_pages` table; no client round-trip.
 */
final class ManualController extends Controller
{
    public function __construct(private readonly DocPagePresenter $presenter) {}

    public function show(string $slug): View|RedirectResponse
    {
        $page = DocPage::query()->where('slug', $slug)->first();

        if ($page === null) {
            // Allow short function URLs like /manual/strlen by resolving the slug
            // as a function name, then 301-ing to the canonical (path-derived) URL.
            if (($canonical = $this->resolveFunctionAlias($slug)) !== null) {
                return redirect()->route('manual.show', ['slug' => $canonical], 301);
            }

            throw new NotFoundHttpException("No manual page for [{$slug}].");
        }

        $doc = $this->presenter->present($page);
        $dir = $this->directoryOf($page->source_path);

        return view('manual.show', [
            'doc' => $doc,
            'dir' => $dir,
            'siblings' => $this->siblings($dir),
            'children' => $this->children($page->source_path),
        ]);
    }

    /**
     * Resolve a short alias (a bare function/method name) to a canonical slug.
     * Prefers the shortest source path so a core function wins over an extension
     * variant. Returns null when there's no clean match.
     */
    private function resolveFunctionAlias(string $slug): ?string
    {
        $name = trim($slug);

        if ($name === '' || str_contains($name, '/')) {
            return null;
        }

        $match = DocPage::query()
            ->where('type', 'refentry')
            ->whereRaw('lower(title) = ?', [mb_strtolower($name)])
            ->orderByRaw('length(source_path)')
            ->value('slug');

        return is_string($match) ? $match : null;
    }

    /**
     * Pages in the same source directory (for the contextual sidebar). The
     * current page is included so the view can highlight it as active rather
     * than dropping it from the list.
     *
     * @return list<array{slug: string, name: string, kind: string, desc: string}>
     */
    private function siblings(string $dir): array
    {
        if ($dir === '') {
            return [];
        }

        return array_values($this->pagesDirectlyIn($dir)
            ->map(fn (DocPage $p) => $this->presenter->summary($p))
            ->all());
    }

    /**
     * Child pages owned by an index page: "language/types.xml" owns the pages
     * directly under "language/types/" (the "In this section" grid).
     *
     * @return list<array{slug: string, name: string, kind: string, desc: string}>
     */
    private function children(string $sourcePath): array
    {
        if (! str_ends_with(strtolower($sourcePath), '.xml')) {
            return [];
        }

        $childDir = substr($sourcePath, 0, -4);

        return array_values($this->pagesDirectlyIn($childDir)
            ->map(fn (DocPage $p) => $this->presenter->summary($p))
            ->all());
    }

    /**
     * Pages whose source file lives directly in $dir (not in a sub-directory).
     *
     * @return Collection<int, DocPage>
     */
    private function pagesDirectlyIn(string $dir): Collection
    {
        return DocPage::query()
            ->where('source_path', 'like', $dir.'/%')
            ->orderBy('title')
            ->limit(400)
            ->get(['slug', 'title', 'type', 'source_path', 'metadata'])
            ->filter(fn (DocPage $p) => $this->directoryOf($p->source_path) === $dir);
    }

    private function directoryOf(string $sourcePath): string
    {
        $i = strrpos($sourcePath, '/');

        return $i === false ? '' : substr($sourcePath, 0, $i);
    }
}
