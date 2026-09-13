<?php

declare(strict_types=1);

namespace App\Docs\Parser;

use App\Docs\DTO\DocPageDTO;
use App\Docs\DTO\MetadataDTO;
use App\Docs\DTO\SectionDTO;
use App\Docs\Enums\DocumentType;
use App\Docs\Exceptions\InvalidDocumentStructureException;
use App\Docs\Rendering\DocBookHtmlRenderer;
use App\Docs\Support\LoadedDocument;
use DOMElement;

/**
 * Base for prose-style documents (chapter, article, appendix, …): a title plus
 * introductory paragraphs and top-level sections. Concrete subclasses only
 * declare which types they handle and which type to emit.
 */
abstract class NarrativeDocumentParser extends AbstractDocumentParser
{
    abstract protected function emitType(): DocumentType;

    protected function parseDocument(LoadedDocument $document): DocPageDTO
    {
        $dom = $document->document();
        $source = $document->source;
        $root = $dom->documentElement;

        $title = $this->firstText($dom, 'title');
        if ($root === null || $title === null || $title === '') {
            throw InvalidDocumentStructureException::missing('<title>', $source->relativePath);
        }

        $intro = $this->introParagraphs($root);
        $sections = $this->topLevelSections($root);
        $bodyHtml = (new DocBookHtmlRenderer)->render($root);
        $version = $this->versionInfoFor($this->printVersionKey($root));

        // Lead-in prose for the excerpt/meta description: the direct intro
        // paragraphs, or the first prose paragraph anywhere when the body nests
        // them inside a <simplesect>/<sect1> (as many chapters do).
        $purpose = $intro[0] ?? $this->firstProseParagraph($root);

        return new DocPageDTO(
            docId: $source->docId(),
            slug: $source->slug(),
            title: $title,
            type: $this->emitType(),
            sourcePath: $source->relativePath,
            metadata: new MetadataDTO(
                purpose: $purpose,
                version: $version?->from,
                extra: array_filter([
                    'deprecated' => $version?->deprecated,
                    'removed' => $version?->removed,
                ], static fn (?string $value): bool => $value !== null),
            ),
            rawXml: $document->rawXml(),
            content: $intro !== [] ? implode("\n\n", $intro) : ($purpose ?? ''),
            sections: $sections,
            bodyHtml: $bodyHtml,
        );
    }

    /** Block elements whose text is source code, not prose. */
    private const CODE_BLOCKS = ['programlisting', 'screen', 'example', 'informalexample', 'literallayout'];

    /**
     * Direct-child intro prose of the document root. DocBook uses both <para>
     * and <simpara> for lead-in text; paragraphs that merely wrap a code example
     * (e.g. <para><informalexample>…</informalexample></para>) are skipped so the
     * excerpt/meta description never becomes a dump of example code.
     *
     * @return list<string>
     */
    private function introParagraphs(DOMElement $root): array
    {
        $paras = [];
        foreach ($root->childNodes as $child) {
            if (! $child instanceof DOMElement || ! in_array($child->localName, ['para', 'simpara'], true)) {
                continue;
            }
            if ($this->wrapsCode($child)) {
                continue;
            }
            $text = $this->text($child);
            if ($text !== '') {
                $paras[] = $text;
            }
        }

        return $paras;
    }

    /**
     * First prose paragraph (<para>/<simpara>) anywhere in the tree, skipping any
     * that only wrap a code example.
     */
    private function firstProseParagraph(DOMElement $root): ?string
    {
        foreach ($root->getElementsByTagName('*') as $node) {
            if (! in_array($node->localName, ['para', 'simpara'], true) || $this->wrapsCode($node)) {
                continue;
            }
            $text = $this->text($node);
            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }

    /**
     * Whether a paragraph contains a code/example block (so its text content is
     * source code rather than prose).
     */
    private function wrapsCode(DOMElement $para): bool
    {
        foreach (self::CODE_BLOCKS as $block) {
            if ($para->getElementsByTagName($block)->length > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Each <sect1>/<section> becomes a {@see SectionDTO}.
     *
     * @return list<SectionDTO>
     */
    private function topLevelSections(DOMElement $root): array
    {
        $sections = [];
        foreach ($root->childNodes as $child) {
            if (! $child instanceof DOMElement || ! in_array($child->localName, ['sect1', 'sect2', 'sect3', 'section'], true)) {
                continue;
            }
            $heading = $this->firstText($child, 'title') ?? '';
            $sections[] = new SectionDTO(
                id: $child->getAttribute('xml:id') ?: $child->getAttribute('id'),
                title: $heading,
                content: $this->firstText($child, 'para') ?? '',
            );
        }

        return $sections;
    }
}
