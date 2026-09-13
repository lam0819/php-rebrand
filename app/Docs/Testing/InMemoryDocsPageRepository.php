<?php

declare(strict_types=1);

namespace App\Docs\Testing;

use App\Docs\Contracts\DocsPageRepository;
use App\Docs\DTO\DocPageDTO;

/**
 * An in-memory {@see DocsPageRepository} for tests — no database required.
 * Pages are upserted by doc id, mirroring the Eloquent repository's semantics.
 */
final class InMemoryDocsPageRepository implements DocsPageRepository
{
    /** @var array<string, DocPageDTO> */
    private array $pages = [];

    /** @var array<string, string> docId => source hash */
    private array $hashes = [];

    public function save(DocPageDTO $page, ?string $sourceHash = null): void
    {
        $this->pages[$page->docId] = $page;

        if ($sourceHash !== null) {
            $this->hashes[$page->docId] = $sourceHash;
        }
    }

    public function existsForDocId(string $docId): bool
    {
        return isset($this->pages[$docId]);
    }

    public function sourceHashFor(string $docId): ?string
    {
        return $this->hashes[$docId] ?? null;
    }

    /**
     * @return array<string, DocPageDTO>
     */
    public function all(): array
    {
        return $this->pages;
    }

    public function find(string $docId): ?DocPageDTO
    {
        return $this->pages[$docId] ?? null;
    }

    public function count(): int
    {
        return count($this->pages);
    }
}
