<?php

declare(strict_types=1);

namespace App\Docs\Parser;

use App\Docs\Enums\DocumentType;

/**
 * Parses <article> pages — standalone guide-style documents.
 */
final class ArticleParser extends NarrativeDocumentParser
{
    protected function supportedTypes(): array
    {
        return [DocumentType::Article];
    }

    protected function emitType(): DocumentType
    {
        return DocumentType::Article;
    }
}
