<?php

declare(strict_types=1);

namespace App\Docs\Importer;

use App\Docs\Contracts\XmlFileFinder;
use App\Docs\Support\SourceFile;
use FilesystemIterator;
use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Finds every `*.xml` file beneath a root directory, lazily, yielding a
 * {@see SourceFile} for each with its path relative to that root.
 */
final class RecursiveXmlFileFinder implements XmlFileFinder
{
    /**
     * @return Generator<SourceFile>
     */
    public function find(string $root): Generator
    {
        $root = rtrim($root, '/\\');

        if (! is_dir($root)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || strtolower($file->getExtension()) !== 'xml') {
                continue;
            }

            $absolute = $file->getPathname();
            $relative = ltrim(substr($absolute, strlen($root)), '/\\');

            yield new SourceFile($absolute, $relative);
        }
    }
}
