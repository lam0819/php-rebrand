<?php

declare(strict_types=1);

namespace App\Docs\DTO;

use App\Docs\Enums\DocumentType;

/**
 * The canonical, parser-agnostic representation of a single documentation page.
 *
 * This is the boundary type of the pipeline: parsers produce it, and everything
 * downstream (normalize, persist, render, index) consumes it. It contains plain
 * PHP values only — never SimpleXML, DOMDocument, or DOMElement.
 */
final readonly class DocPageDTO
{
    /**
     * @param  list<SectionDTO>  $sections
     * @param  list<ExampleDTO>  $examples
     * @param  list<ParameterDTO>  $parameters
     * @param  list<SeeAlsoDTO>  $seeAlso
     */
    public function __construct(
        public string $docId,
        public string $slug,
        public string $title,
        public DocumentType $type,
        public string $sourcePath,
        public MetadataDTO $metadata,
        public ?string $rawXml = null,
        public ?string $content = null,
        public array $sections = [],
        public array $examples = [],
        public array $parameters = [],
        public ?ReturnValueDTO $returnValue = null,
        public array $seeAlso = [],
        public ?string $bodyHtml = null,
    ) {}

    /**
     * Return a copy with the content replaced — used by normalizers, which must
     * not mutate the immutable DTO in place.
     */
    public function withContent(?string $content): self
    {
        return new self(
            docId: $this->docId,
            slug: $this->slug,
            title: $this->title,
            type: $this->type,
            sourcePath: $this->sourcePath,
            metadata: $this->metadata,
            rawXml: $this->rawXml,
            content: $content,
            sections: $this->sections,
            examples: $this->examples,
            parameters: $this->parameters,
            returnValue: $this->returnValue,
            seeAlso: $this->seeAlso,
            bodyHtml: $this->bodyHtml,
        );
    }

    /**
     * Return a copy with the title replaced.
     */
    public function withTitle(string $title): self
    {
        return new self(
            docId: $this->docId,
            slug: $this->slug,
            title: $title,
            type: $this->type,
            sourcePath: $this->sourcePath,
            metadata: $this->metadata,
            rawXml: $this->rawXml,
            content: $this->content,
            sections: $this->sections,
            examples: $this->examples,
            parameters: $this->parameters,
            returnValue: $this->returnValue,
            seeAlso: $this->seeAlso,
            bodyHtml: $this->bodyHtml,
        );
    }

    /**
     * The structured payload persisted to the `metadata` JSON column.
     *
     * @return array<string, mixed>
     */
    public function structuredMetadata(): array
    {
        return [
            'metadata' => $this->metadata->toArray(),
            'sections' => array_map(static fn (SectionDTO $s): array => $s->toArray(), $this->sections),
            'examples' => array_map(static fn (ExampleDTO $e): array => $e->toArray(), $this->examples),
            'parameters' => array_map(static fn (ParameterDTO $p): array => $p->toArray(), $this->parameters),
            'returnValue' => $this->returnValue?->toArray(),
            'seeAlso' => array_map(static fn (SeeAlsoDTO $s): array => $s->toArray(), $this->seeAlso),
        ];
    }
}
