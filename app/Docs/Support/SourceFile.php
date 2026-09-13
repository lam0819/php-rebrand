<?php

declare(strict_types=1);

namespace App\Docs\Support;

/**
 * An XML source file discovered on disk, identified by both its absolute path
 * and its path relative to the import root (used to derive stable slugs/ids).
 */
final readonly class SourceFile
{
    public function __construct(
        public string $absolutePath,
        public string $relativePath,
    ) {}

    /**
     * A stable identifier derived from the relative path, without extension.
     *
     * e.g. "reference/array/functions/array-map.xml" -> "reference.array.functions.array-map"
     */
    public function docId(): string
    {
        $withoutExtension = preg_replace('/\.xml$/i', '', $this->relativePath) ?? $this->relativePath;

        return str_replace(['/', '\\'], '.', trim($withoutExtension, '/\\'));
    }

    /**
     * A URL-friendly slug derived from the relative path.
     */
    public function slug(): string
    {
        $withoutExtension = preg_replace('/\.xml$/i', '', $this->relativePath) ?? $this->relativePath;

        return str_replace(['/', '\\', '.', '_'], '-', strtolower(trim($withoutExtension, '/\\')));
    }
}
