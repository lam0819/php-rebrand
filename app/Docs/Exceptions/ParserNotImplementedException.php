<?php

declare(strict_types=1);

namespace App\Docs\Exceptions;

use App\Docs\Enums\DocumentType;

/**
 * Thrown by the concrete parsers shipped in this first architecture PR.
 *
 * The parsing strategy for each document type is intentionally not implemented
 * yet — this exception makes that explicit rather than silently returning an
 * empty document. Replace the parser body and remove this in a later PR.
 */
final class ParserNotImplementedException extends ImporterException
{
    public static function forType(DocumentType $type): self
    {
        return new self(
            "Parsing for document type [{$type->value}] is not implemented yet. ".
            'This PR establishes the pipeline architecture only.'
        );
    }
}
