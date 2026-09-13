<?php

declare(strict_types=1);

namespace App\Docs;

use App\Docs\Contracts\ContentNormalizer;
use App\Docs\Contracts\DocsPageRepository;
use App\Docs\Contracts\DocumentClassifier;
use App\Docs\Contracts\DocumentImporter;
use App\Docs\Contracts\DocumentParser;
use App\Docs\Contracts\ParserRegistry;
use App\Docs\Contracts\XmlDocumentLoader;
use App\Docs\Contracts\XmlFileFinder;
use App\Docs\Importer\DocumentParserRegistry;
use App\Docs\Importer\DomXmlDocumentLoader;
use App\Docs\Importer\RecursiveXmlFileFinder;
use App\Docs\Importer\RootElementDocumentClassifier;
use App\Docs\Normalizer\DefaultContentNormalizer;
use App\Docs\Parser\AppendixParser;
use App\Docs\Parser\ArticleParser;
use App\Docs\Parser\ChapterParser;
use App\Docs\Parser\RefEntryParser;
use App\Docs\Persistence\EloquentDocsPageRepository;
use App\Docs\Services\ImporterService;
use App\Docs\Versions\VersionCatalog;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Binds every pipeline contract to its default implementation and assembles the
 * parser registry. Swapping any layer — a different storage backend, an extra
 * parser, a new normalizer — is a one-line override of these bindings.
 */
final class DocsServiceProvider extends ServiceProvider
{
    /**
     * The default parsers registered with the {@see ParserRegistry}.
     *
     * @var list<class-string<DocumentParser>>
     */
    private const PARSERS = [
        RefEntryParser::class,
        ChapterParser::class,
        ArticleParser::class,
        AppendixParser::class,
    ];

    public function register(): void
    {
        $this->app->bind(XmlFileFinder::class, RecursiveXmlFileFinder::class);
        $this->app->bind(XmlDocumentLoader::class, DomXmlDocumentLoader::class);
        $this->app->bind(DocumentClassifier::class, RootElementDocumentClassifier::class);
        $this->app->bind(ContentNormalizer::class, DefaultContentNormalizer::class);
        $this->app->bind(DocsPageRepository::class, EloquentDocsPageRepository::class);

        // The version catalog scans the source tree once; share a single instance.
        $this->app->singleton(VersionCatalog::class, static fn (): VersionCatalog => new VersionCatalog(config()->string('docs.source.path')));

        $this->app->singleton(ParserRegistry::class, function (Application $app): ParserRegistry {
            $registry = new DocumentParserRegistry;

            foreach (self::PARSERS as $parser) {
                $registry->register($app->make($parser));
            }

            return $registry;
        });

        $this->app->bind(DocumentImporter::class, ImporterService::class);
    }
}
