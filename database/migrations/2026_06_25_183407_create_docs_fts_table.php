<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A standalone FTS5 full-text index over the manual. It carries everything the
 * search UI renders (slug, type, title, purpose) so a query needs no join back
 * to docs_pages. Populated by `docs:reindex` (and after each import).
 *
 * FTS5 is SQLite-specific; on other drivers we skip it and search falls back to
 * LIKE. See App\Docs\Search\DocSearch.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement(<<<'SQL'
            CREATE VIRTUAL TABLE IF NOT EXISTS docs_fts USING fts5(
                doc_id UNINDEXED,
                slug UNINDEXED,
                type UNINDEXED,
                title,
                purpose,
                tokenize = 'unicode61'
            )
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('docs_fts');
    }
};
