<?php

declare(strict_types=1);

namespace App\Docs\Contracts;

use App\Docs\DTO\ImportReport;

/**
 * Coordinates a full import run over an XML documentation tree.
 *
 * The orchestrator wires together discovery, loading, classification, parsing,
 * normalization and persistence. It never touches XML itself.
 */
interface DocumentImporter
{
    /**
     * Import every supported document found beneath the given root.
     *
     * When $force is false, documents whose source XML is unchanged since the
     * last import (by content hash) are skipped as "unchanged".
     */
    public function import(string $root, bool $force = false): ImportReport;
}
