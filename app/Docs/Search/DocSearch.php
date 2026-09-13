<?php

declare(strict_types=1);

namespace App\Docs\Search;

use App\Docs\Persistence\DocPage;
use App\Docs\Support\DocPagePresenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Full-text search over the manual, backed by SQLite FTS5 (ranked by bm25).
 *
 * The index is a denormalised copy of docs_pages carrying just what the search
 * UI shows, so a lookup never joins back. When FTS5 isn't available (non-SQLite
 * driver, or the index hasn't been built yet) callers fall back to LIKE via
 * {@see self::available()}.
 */
final class DocSearch
{
    public function __construct(private readonly DocPagePresenter $presenter) {}

    /**
     * Whether the FTS index can be used right now.
     */
    public function available(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite' && Schema::hasTable('docs_fts');
    }

    /**
     * Ranked summaries for a query, or null when FTS isn't usable (caller should
     * fall back to a LIKE search). Optionally constrained to a single doc type.
     *
     * @return list<array{slug: string, name: string, kind: string, desc: string}>|null
     */
    public function summaries(string $term, int $limit = 10, ?string $type = null): ?array
    {
        if (! $this->available()) {
            return null;
        }

        $match = $this->matchQuery($term);

        if ($match === '') {
            return [];
        }

        $sql = 'SELECT slug, title, type, purpose FROM docs_fts WHERE docs_fts MATCH ?';
        $params = [$match];

        if ($type !== null && $type !== '') {
            $sql .= ' AND type = ?';
            $params[] = $type;
        }

        $sql .= ' ORDER BY bm25(docs_fts, 10.0, 1.0) LIMIT ?';
        $params[] = $limit;

        try {
            /** @var list<object{slug: string, title: string, type: string, purpose: ?string}> $rows */
            $rows = DB::select($sql, $params);
        } catch (Throwable) {
            return null;
        }

        return array_map(static fn (object $r): array => [
            'slug' => (string) $r->slug,
            'name' => (string) $r->title,
            'kind' => (string) $r->type,
            'desc' => (string) ($r->purpose ?? ''),
        ], $rows);
    }

    /**
     * Total number of pages matching a query (optionally one type), or null when
     * FTS isn't usable.
     */
    public function count(string $term, ?string $type = null): ?int
    {
        if (! $this->available()) {
            return null;
        }

        $match = $this->matchQuery($term);

        if ($match === '') {
            return 0;
        }

        $sql = 'SELECT count(*) AS aggregate FROM docs_fts WHERE docs_fts MATCH ?';
        $params = [$match];

        if ($type !== null && $type !== '') {
            $sql .= ' AND type = ?';
            $params[] = $type;
        }

        try {
            $row = DB::selectOne($sql, $params);
        } catch (Throwable) {
            return null;
        }

        if (is_object($row) && property_exists($row, 'aggregate') && is_numeric($row->aggregate)) {
            return (int) $row->aggregate;
        }

        return 0;
    }

    /**
     * Rebuild the whole index from docs_pages. Cheap enough (~11k rows) to run
     * after every import rather than maintaining per-row triggers.
     */
    public function rebuild(): int
    {
        if (DB::connection()->getDriverName() !== 'sqlite' || ! Schema::hasTable('docs_fts')) {
            return 0;
        }

        DB::statement('DELETE FROM docs_fts');

        $count = 0;
        DocPage::query()
            ->select(['doc_id', 'slug', 'title', 'type', 'metadata'])
            ->orderBy('id')
            ->chunk(1000, function ($pages) use (&$count): void {
                $rows = $pages->map(fn (DocPage $p): array => [
                    'doc_id' => $p->doc_id,
                    'slug' => $p->slug,
                    'type' => $p->type,
                    'title' => $p->title,
                    'purpose' => $this->presenter->purpose($p) ?? '',
                ])->all();

                DB::table('docs_fts')->insert($rows);
                $count += count($rows);
            });

        return $count;
    }

    /**
     * Build a safe FTS5 MATCH expression: each word becomes a quoted prefix term
     * AND-ed together. Quoting escapes FTS operators so user input can't break
     * the query (e.g. "str_replace" -> "str"* AND "replace"*).
     */
    private function matchQuery(string $term): string
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', trim($term), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $tokens = array_map(
            static fn (string $w): string => '"'.str_replace('"', '""', $w).'"*',
            $words,
        );

        return implode(' AND ', $tokens);
    }
}
