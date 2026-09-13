<?php

declare(strict_types=1);

namespace App\Docs\Versions;

/**
 * Per-symbol PHP version availability, as published in php/doc-en's distributed
 * <code>versions.xml</code> files (e.g. <code>from="PHP 4, PHP 5, PHP 7, PHP 8"</code>).
 */
final readonly class VersionInfo
{
    public function __construct(
        public string $from,
        public ?string $deprecated = null,
        public ?string $removed = null,
    ) {}
}
