<?php

declare(strict_types=1);

namespace App\Docs\Parser;

use App\Docs\Enums\DocumentType;

/**
 * Parses <appendix> pages — supplementary reference material (e.g. lists of
 * constants, migration notes).
 */
final class AppendixParser extends NarrativeDocumentParser
{
    protected function supportedTypes(): array
    {
        return [DocumentType::Appendix];
    }

    protected function emitType(): DocumentType
    {
        return DocumentType::Appendix;
    }
}
