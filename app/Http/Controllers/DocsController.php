<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Docs\Persistence\DocPage;
use App\Docs\Search\DocSearch;
use App\Docs\Support\DocPagePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only JSON API over the imported documentation. Shares the same
 * {@see DocPagePresenter} as the server-rendered pages so both expose an
 * identical shape.
 */
final class DocsController extends Controller
{
    public function __construct(private readonly DocPagePresenter $presenter) {}

    public function index(Request $request): JsonResponse
    {
        $query = trim((string) $request->string('q'));
        $type = trim((string) $request->string('type'));
        $dir = trim((string) $request->string('dir'));
        $perPage = min(max($request->integer('per_page', 60), 1), 100);
        $page = max($request->integer('page', 1), 1);

        $builder = DocPage::query()->orderBy('title');

        if ($query !== '') {
            $like = '%'.$query.'%';
            $builder->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('slug', 'like', $like));
        }

        if ($type !== '') {
            $builder->where('type', $type);
        }

        if ($dir !== '') {
            $builder->where('source_path', 'like', $dir.'/%');
        }

        $total = (clone $builder)->count();
        $items = $builder->forPage($page, $perPage)
            ->get(['slug', 'title', 'type', 'metadata'])
            ->map(fn (DocPage $p) => $this->presenter->summary($p));

        $counts = DocPage::query()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return response()->json([
            'total' => $total,
            'counts' => $counts,
            'page' => $page,
            'perPage' => $perPage,
            'items' => $items,
        ]);
    }

    public function search(Request $request, DocSearch $search): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return response()->json(['query' => '', 'results' => []]);
        }

        // Prefer the ranked FTS5 index; fall back to LIKE when unavailable.
        $results = $search->summaries($query, 10);

        if ($results === null) {
            $like = '%'.$query.'%';
            $results = DocPage::query()
                ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('slug', 'like', $like))
                ->orderByRaw('case when title like ? then 0 else 1 end', [$query.'%'])
                ->orderBy('title')
                ->limit(10)
                ->get(['slug', 'title', 'type', 'metadata'])
                ->map(fn (DocPage $p) => $this->presenter->summary($p))
                ->all();
        }

        return response()->json([
            'query' => $query,
            'results' => $results,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $page = DocPage::query()->where('slug', $slug)->first();

        if ($page === null) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($this->presenter->present($page));
    }
}
