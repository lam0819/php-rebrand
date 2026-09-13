<?php

declare(strict_types=1);

namespace App\Docs\Testing;

use App\Docs\Contracts\DocumentParser;
use App\Docs\DTO\DocPageDTO;
use App\Docs\DTO\MetadataDTO;
use App\Docs\Enums\DocumentType;
use App\Docs\Support\LoadedDocument;

/**
 * A configurable parser test double that produces a deterministic {@see DocPageDTO}
 * from a document's source metadata — without any real XML parsing.
 *
 * Useful for exercising the orchestration (find → classify → parse → persist)
 * before the concrete parsers are implemented. Ships in the library so that
 * downstream applications can test their own pipeline wiring the same way.
 */
final class FakeDocumentParser implements DocumentParser
{
    /**
     * @param  list<DocumentType>  $supportedTypes
     */
    public function __construct(
        private readonly array $supportedTypes,
        private readonly DocumentType $emitType = DocumentType::RefEntry,
    ) {}

    public function supports(DocumentType $type): bool
    {
        return in_array($type, $this->supportedTypes, true);
    }

    public function parse(LoadedDocument $document): DocPageDTO
    {
        $source = $document->source;

        return new DocPageDTO(
            docId: $source->docId(),
            slug: $source->slug(),
            title: $source->docId(),
            type: $this->emitType,
            sourcePath: $source->relativePath,
            metadata: new MetadataDTO(purpose: 'parsed by FakeDocumentParser'),
            rawXml: $document->rawXml(),
            content: 'fake content',
        );
    }
}
