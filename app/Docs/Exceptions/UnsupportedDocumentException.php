<?php

declare(strict_types=1);

namespace App\Docs\Exceptions;

use App\Docs\Enums\DocumentType;

/**
 * Thrown when a document is recognised but not of an importable type.
 */
final class UnsupportedDocumentException extends ImporterException
{
    public static function forType(DocumentType $type, string $path): self
    {
        return new self("Document type [{$type->value}] at [{$path}] is not supported for import.");
    }
}
