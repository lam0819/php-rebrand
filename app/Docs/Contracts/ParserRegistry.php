<?php

declare(strict_types=1);

namespace App\Docs\Contracts;

use App\Docs\Enums\DocumentType;
use App\Docs\Exceptions\ParserNotFoundException;

/**
 * A registry of {@see DocumentParser} implementations, resolvable by document
 * type. This is the extension point: registering a new parser is all that is
 * needed to support a new document type or an entirely new documentation source.
 */
interface ParserRegistry
{
    public function register(DocumentParser $parser): void;

    /**
     * @throws ParserNotFoundException when no registered parser supports the type
     */
    public function resolve(DocumentType $type): DocumentParser;

    public function has(DocumentType $type): bool;
}
