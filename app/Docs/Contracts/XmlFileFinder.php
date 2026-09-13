<?php

declare(strict_types=1);

namespace App\Docs\Contracts;

use App\Docs\Support\SourceFile;

/**
 * Discovers XML source files beneath an import root.
 *
 * Implementations should be lazy where possible (returning a generator) so the
 * pipeline can stream large documentation trees without loading every path into
 * memory at once. Incremental/changed-file discovery is a future concern that
 * this contract intentionally leaves open.
 */
interface XmlFileFinder
{
    /**
     * @return iterable<SourceFile>
     */
    public function find(string $root): iterable;
}
