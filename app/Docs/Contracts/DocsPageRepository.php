<?php

declare(strict_types=1);

namespace App\Docs\Contracts;

use App\Docs\DTO\DocPageDTO;

/**
 * Persists canonical document pages.
 *
 * The pipeline depends on this abstraction rather than Eloquent directly, so the
 * storage backend (SQLite, Postgres, an in-memory test double) is swappable.
 */
interface DocsPageRepository
{
    /**
     * Insert or update the page identified by its doc id, optionally recording
     * the content hash of the source file it was built from.
     */
    public function save(DocPageDTO $page, ?string $sourceHash = null): void;

    public function existsForDocId(string $docId): bool;

    /**
     * The stored source-content hash for a doc id, or null if absent/unknown.
     * Used to skip re-importing unchanged documents.
     */
    public function sourceHashFor(string $docId): ?string;
}
