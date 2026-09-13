<?php

declare(strict_types=1);

namespace App\Docs\Persistence;

use App\Docs\Contracts\DocsPageRepository;
use App\Docs\DTO\DocPageDTO;

/**
 * Persists document pages with Eloquent, upserting on the stable `doc_id` so
 * re-importing a document updates the existing row rather than duplicating it.
 */
final class EloquentDocsPageRepository implements DocsPageRepository
{
    public function save(DocPageDTO $page, ?string $sourceHash = null): void
    {
        DocPage::query()->updateOrCreate(
            ['doc_id' => $page->docId],
            [
                'slug' => $page->slug,
                'title' => $page->title,
                'type' => $page->type->value,
                'source_path' => $page->sourcePath,
                'source_hash' => $sourceHash,
                'raw_xml' => $page->rawXml,
                'content' => $page->content,
                'body_html' => $page->bodyHtml,
                'metadata' => $page->structuredMetadata(),
            ],
        );
    }

    public function existsForDocId(string $docId): bool
    {
        return DocPage::query()->where('doc_id', $docId)->exists();
    }

    public function sourceHashFor(string $docId): ?string
    {
        $hash = DocPage::query()->where('doc_id', $docId)->value('source_hash');

        return is_string($hash) ? $hash : null;
    }
}
