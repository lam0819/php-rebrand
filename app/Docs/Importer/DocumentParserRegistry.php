<?php

declare(strict_types=1);

namespace App\Docs\Importer;

use App\Docs\Contracts\DocumentParser;
use App\Docs\Contracts\ParserRegistry;
use App\Docs\Enums\DocumentType;
use App\Docs\Exceptions\ParserNotFoundException;

/**
 * In-memory parser registry. The first parser whose {@see DocumentParser::supports()}
 * returns true for a type wins, so registration order expresses precedence.
 */
final class DocumentParserRegistry implements ParserRegistry
{
    /** @var list<DocumentParser> */
    private array $parsers = [];

    /**
     * @param  iterable<DocumentParser>  $parsers
     */
    public function __construct(iterable $parsers = [])
    {
        foreach ($parsers as $parser) {
            $this->register($parser);
        }
    }

    public function register(DocumentParser $parser): void
    {
        $this->parsers[] = $parser;
    }

    public function resolve(DocumentType $type): DocumentParser
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($type)) {
                return $parser;
            }
        }

        throw ParserNotFoundException::forType($type);
    }

    public function has(DocumentType $type): bool
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($type)) {
                return true;
            }
        }

        return false;
    }
}
