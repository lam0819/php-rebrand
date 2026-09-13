<?php

declare(strict_types=1);

namespace App\Docs\Exceptions;

/**
 * Thrown by a parser when a document is well-formed XML but does not contain
 * the structure the parser requires (e.g. a <refentry> with no <refnamediv>).
 */
final class InvalidDocumentStructureException extends ImporterException
{
    public static function missing(string $what, string $path): self
    {
        return new self("Document at [{$path}] is missing required structure: {$what}.");
    }
}
