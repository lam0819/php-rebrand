<?php

declare(strict_types=1);

namespace App\Docs\Versions;

use DOMDocument;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * The PHP manual's per-symbol version availability ("(PHP 4, PHP 5, PHP 7, PHP 8)").
 *
 * php.net resolves this at build time from the distributed
 * <code>*&#47;versions.xml</code> files committed throughout php/doc-en (one per
 * extension/section), keyed by the lower-cased symbol name. We do the same: the
 * whole source tree is scanned once and the name → {@see VersionInfo} map is
 * cached for the lifetime of the import.
 */
final class VersionCatalog
{
    /** @var array<string, VersionInfo>|null */
    private ?array $entries = null;

    public function __construct(private readonly string $sourcePath) {}

    /**
     * Version info for a symbol (function/method/class/construct), or null when
     * the source provides none. Names are matched case-insensitively, matching
     * php.net's normalisation (e.g. the refname "DateTime::add" maps to the
     * versions.xml entry "datetime::add").
     */
    public function lookup(?string $name): ?VersionInfo
    {
        if ($name === null || $name === '') {
            return null;
        }

        $this->entries ??= $this->load();

        return $this->entries[strtolower(trim($name))] ?? null;
    }

    /**
     * @return array<string, VersionInfo>
     */
    private function load(): array
    {
        if (! is_dir($this->sourcePath)) {
            return [];
        }

        $entries = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->sourcePath, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->getFilename() === 'versions.xml') {
                $this->parseFile($file->getPathname(), $entries);
            }
        }

        return $entries;
    }

    /**
     * @param  array<string, VersionInfo>  $entries
     */
    private function parseFile(string $path, array &$entries): void
    {
        $xml = file_get_contents($path);
        if ($xml === false) {
            return;
        }

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return;
        }

        foreach ($dom->getElementsByTagName('function') as $function) {
            $name = strtolower(trim($function->getAttribute('name')));
            $from = trim($function->getAttribute('from'));
            if ($name === '' || $from === '') {
                continue;
            }

            $entries[$name] = new VersionInfo(
                from: $from,
                deprecated: ($function->getAttribute('deprecated') ?: null),
                removed: ($function->getAttribute('removed') ?: null),
            );
        }
    }
}
