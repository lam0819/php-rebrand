<?php

declare(strict_types=1);

namespace App\Docs\Contracts;

use App\Docs\Enums\DocumentType;
use App\Docs\Support\LoadedDocument;

/**
 * Determines the {@see DocumentType} of a loaded document so the correct parser
 * can be selected. Classification must be total — unknown inputs map to
 * {@see DocumentType::Unknown} rather than throwing.
 */
interface DocumentClassifier
{
    public function classify(LoadedDocument $document): DocumentType;
}
