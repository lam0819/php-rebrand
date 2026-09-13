<?php

declare(strict_types=1);

namespace App\Docs\Parser;

use App\Docs\Enums\DocumentType;

/**
 * Parses <chapter> pages (and the structurally similar <part>/<preface>/<set>
 * container pages) — narrative manual sections rather than function references.
 */
final class ChapterParser extends NarrativeDocumentParser
{
    protected function supportedTypes(): array
    {
        return [
            DocumentType::Chapter,
            DocumentType::Part,
            DocumentType::Preface,
            DocumentType::Set,
            DocumentType::Book,
            DocumentType::Reference,
            DocumentType::Section,
            DocumentType::Sect1,
            DocumentType::Sect2,
            DocumentType::Sect3,
        ];
    }

    protected function emitType(): DocumentType
    {
        return DocumentType::Chapter;
    }
}
