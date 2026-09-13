<?php

declare(strict_types=1);

namespace App\Docs\Exceptions;

use App\Docs\Enums\DocumentType;

/**
 * Thrown when no parser in the registry can handle a given document type.
 */
final class ParserNotFoundException extends ImporterException
{
    public static function forType(DocumentType $type): self
    {
        return new self("No parser is registered for document type [{$type->value}].");
    }
}
