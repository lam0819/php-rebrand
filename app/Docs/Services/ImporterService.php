<?php

declare(strict_types=1);

namespace App\Docs\Services;

use App\Docs\Contracts\ContentNormalizer;
use App\Docs\Contracts\DocsPageRepository;
use App\Docs\Contracts\DocumentClassifier;
use App\Docs\Contracts\DocumentImporter;
use App\Docs\Contracts\ParserRegistry;
use App\Docs\Contracts\XmlDocumentLoader;
use App\Docs\Contracts\XmlFileFinder;
use App\Docs\DTO\ImportReport;
use App\Docs\Enums\DocumentType;
use App\Docs\Exceptions\ImporterException;
use App\Docs\Support\LoadedDocument;
use App\Docs\Support\SourceFile;

/**
 * Coordinates the import pipeline end to end. It owns the control flow —
 * find → load → classify → resolve parser → parse → normalize → persist — but
 * delegates every actual operation to an injected collaborator and never
 * touches XML itself.
 *
 * A failure on one document is recorded and the run continues; the returned
 * {@see ImportReport} captures what was imported, skipped, and what failed.
 */
final readonly class ImporterService implements DocumentImporter
{
    public function __construct(
        private XmlFileFinder $finder,
        private XmlDocumentLoader $loader,
        private DocumentClassifier $classifier,
        private ParserRegistry $parsers,
        private ContentNormalizer $normalizer,
        private DocsPageRepository $repository,
    ) {}

    public function import(string $root, bool $force = false): ImportReport
    {
        $report = new ImportReport;

        foreach ($this->finder->find($root) as $file) {
            $this->importFile($file, $report, $force);
        }

        return $report;
    }

    private function importFile(SourceFile $file, ImportReport $report, bool $force): void
    {
        try {
            $hash = $this->hash($file);

            // Skip unchanged files cheaply, before loading/parsing the XML.
            if (! $force && $hash !== null && $this->repository->sourceHashFor($file->docId()) === $hash) {
                $report->recordUnchanged($file->docId());

                return;
            }

            $document = $this->loader->load($file);
            $type = $this->classifier->classify($document);

            if (! $type->isKnown() || ! $this->parsers->has($type)) {
                $report->recordSkipped($file->docId(), "unsupported document type [{$type->value}]");

                return;
            }

            $this->persist($document, $type, $report, $hash);
        } catch (ImporterException $e) {
            $report->recordFailed($file->relativePath, $e->getMessage());
        }
    }

    private function persist(LoadedDocument $document, DocumentType $type, ImportReport $report, ?string $hash): void
    {
        $parser = $this->parsers->resolve($type);

        $page = $parser->parse($document);
        $page = $this->normalizer->normalize($page);

        $this->repository->save($page, $hash);
        $report->recordImported($page->docId);
    }

    /**
     * A fast content hash of the source file, or null if it can't be read
     * (in which case the file is always (re)processed).
     */
    private function hash(SourceFile $file): ?string
    {
        $hash = @hash_file('xxh128', $file->absolutePath);

        return $hash === false ? null : $hash;
    }
}
