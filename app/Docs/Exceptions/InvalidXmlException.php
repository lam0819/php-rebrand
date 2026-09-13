<?php

declare(strict_types=1);

namespace App\Docs\Exceptions;

/**
 * Thrown when a source file cannot be loaded as well-formed XML.
 */
final class InvalidXmlException extends ImporterException
{
    /**
     * @param  list<string>  $libxmlErrors
     */
    public static function forFile(string $path, array $libxmlErrors = []): self
    {
        $detail = $libxmlErrors === []
            ? ''
            : ' ('.implode('; ', array_map(static fn (string $e): string => trim($e), $libxmlErrors)).')';

        return new self("Failed to parse XML document at [{$path}]{$detail}.");
    }
}
