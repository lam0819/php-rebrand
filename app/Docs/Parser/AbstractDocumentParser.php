<?php

declare(strict_types=1);

namespace App\Docs\Parser;

use App\Docs\Contracts\DocumentParser;
use App\Docs\DTO\DocPageDTO;
use App\Docs\Enums\DocumentType;
use App\Docs\Support\LoadedDocument;
use App\Docs\Versions\VersionCatalog;
use App\Docs\Versions\VersionInfo;
use DOMElement;
use DOMNode;
use DOMProcessingInstruction;

/**
 * Shared behaviour for parsers: each declares the document types it supports and
 * implements {@see self::parseDocument()} for the actual extraction.
 *
 * The base provides small, namespace-tolerant DOM read helpers so concrete
 * parsers stay focused on the shape of their document type. The DOM is touched
 * only inside parsers — never past the {@see DocPageDTO} they return.
 */
abstract class AbstractDocumentParser implements DocumentParser
{
    /**
     * The version catalog is optional so parsers can still be constructed
     * directly (e.g. in unit tests); the container injects the shared instance.
     */
    public function __construct(protected readonly ?VersionCatalog $versions = null) {}

    /**
     * The document types this parser is responsible for.
     *
     * @return list<DocumentType>
     */
    abstract protected function supportedTypes(): array;

    abstract protected function parseDocument(LoadedDocument $document): DocPageDTO;

    final public function supports(DocumentType $type): bool
    {
        return in_array($type, $this->supportedTypes(), true);
    }

    final public function parse(LoadedDocument $document): DocPageDTO
    {
        return $this->parseDocument($document);
    }

    /**
     * Version availability for a symbol name, resolved from the source's
     * versions.xml catalog. Returns null when unavailable (e.g. no catalog).
     */
    protected function versionInfoFor(?string $name): ?VersionInfo
    {
        return $this->versions?->lookup($name);
    }

    /**
     * The symbol name a narrative page declares its version for, via the
     * <code>&lt;?phpdoc print-version-for="break"?&gt;</code> processing
     * instruction. Null when the page carries no such instruction.
     */
    protected function printVersionKey(DOMNode $context): ?string
    {
        foreach ($context->childNodes as $child) {
            if ($child instanceof DOMProcessingInstruction
                && $child->target === 'phpdoc'
                && preg_match('/print-version-for="([^"]+)"/', $child->data, $match) === 1) {
                return $match[1];
            }
        }

        return null;
    }

    /**
     * The first element with the given local name anywhere under $context.
     */
    protected function firstElement(DOMNode $context, string $localName): ?DOMElement
    {
        foreach ($this->elements($context, $localName) as $element) {
            return $element;
        }

        return null;
    }

    /**
     * Every element with the given local name under $context (namespace-agnostic).
     *
     * @return list<DOMElement>
     */
    protected function elements(DOMNode $context, string $localName): array
    {
        $owner = $context instanceof \DOMDocument ? $context : $context->ownerDocument;
        if ($owner === null) {
            return [];
        }

        $found = [];
        foreach ($owner->getElementsByTagName($localName) as $node) {
            if ($this->isDescendant($node, $context)) {
                $found[] = $node;
            }
        }

        return $found;
    }

    /**
     * Trimmed, whitespace-collapsed text of the first matching element.
     */
    protected function firstText(DOMNode $context, string $localName): ?string
    {
        $element = $this->firstElement($context, $localName);

        return $element === null ? null : $this->text($element);
    }

    /**
     * Trimmed text content of a node with internal whitespace collapsed.
     */
    protected function text(?DOMNode $node): string
    {
        if ($node === null) {
            return '';
        }

        $collapsed = preg_replace('/\s+/', ' ', $node->textContent) ?? $node->textContent;

        return trim($collapsed);
    }

    private function isDescendant(DOMNode $node, DOMNode $ancestor): bool
    {
        if ($ancestor instanceof \DOMDocument) {
            return true;
        }

        for ($current = $node->parentNode; $current !== null; $current = $current->parentNode) {
            if ($current->isSameNode($ancestor)) {
                return true;
            }
        }

        return false;
    }
}
