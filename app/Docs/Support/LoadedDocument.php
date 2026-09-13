<?php

declare(strict_types=1);

namespace App\Docs\Support;

use DOMDocument;

/**
 * The internal carrier that moves a parsed DOM tree through the pipeline from
 * the loader to the parser.
 *
 * This is deliberately the ONE place the DOMDocument is allowed to live outside
 * a parser implementation. The DOM is exposed only via {@see self::document()},
 * which parsers call; every other stage uses {@see self::rootElementName()} and
 * never touches a DOM node. Downstream value objects are the plain DTOs.
 */
final readonly class LoadedDocument
{
    public function __construct(
        public SourceFile $source,
        private DOMDocument $document,
    ) {}

    /**
     * The lower-cased name of the document's root element, used for
     * classification without inspecting the rest of the tree.
     */
    public function rootElementName(): string
    {
        $root = $this->document->documentElement;

        return $root === null ? '' : strtolower($root->localName ?? $root->nodeName);
    }

    /**
     * The underlying DOM. Intended for parser implementations only.
     */
    public function document(): DOMDocument
    {
        return $this->document;
    }

    /**
     * The raw XML string of the document, suitable for archival storage.
     */
    public function rawXml(): string
    {
        return (string) $this->document->saveXML();
    }
}
