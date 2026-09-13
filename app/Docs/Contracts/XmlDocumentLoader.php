<?php

declare(strict_types=1);

namespace App\Docs\Contracts;

use App\Docs\Exceptions\InvalidXmlException;
use App\Docs\Support\LoadedDocument;
use App\Docs\Support\SourceFile;

/**
 * Safely loads an XML source file into a DOM tree.
 *
 * Implementations are responsible for hardening (e.g. disabling external entity
 * resolution) and for translating malformed input into a domain exception.
 */
interface XmlDocumentLoader
{
    /**
     * @throws InvalidXmlException when the file is missing or not well-formed XML
     */
    public function load(SourceFile $file): LoadedDocument;
}
