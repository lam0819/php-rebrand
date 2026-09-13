<?php

declare(strict_types=1);

namespace App\Docs\Importer;

use App\Docs\Contracts\DocumentClassifier;
use App\Docs\Enums\DocumentType;
use App\Docs\Support\LoadedDocument;

/**
 * Classifies a document purely from its XML root element name, which in the
 * DocBook source maps one-to-one onto our {@see DocumentType} enum.
 */
final class RootElementDocumentClassifier implements DocumentClassifier
{
    public function classify(LoadedDocument $document): DocumentType
    {
        return DocumentType::fromRootElement($document->rootElementName());
    }
}
