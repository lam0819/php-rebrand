<?php

declare(strict_types=1);

namespace App\Docs\Contracts;

use App\Docs\DTO\DocPageDTO;
use App\Docs\Enums\DocumentType;
use App\Docs\Exceptions\InvalidDocumentStructureException;
use App\Docs\Support\LoadedDocument;

/**
 * Translates a loaded XML document into the canonical {@see DocPageDTO}.
 *
 * Each parser handles exactly one family of document types. The DOM tree is
 * accessed only here, via {@see LoadedDocument::document()}; the produced DTO
 * exposes plain values only.
 */
interface DocumentParser
{
    /**
     * Whether this parser can handle the given document type.
     */
    public function supports(DocumentType $type): bool;

    /**
     * @throws InvalidDocumentStructureException when required structure is absent
     */
    public function parse(LoadedDocument $document): DocPageDTO;
}
