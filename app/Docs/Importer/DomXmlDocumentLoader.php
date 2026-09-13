<?php

declare(strict_types=1);

namespace App\Docs\Importer;

use App\Docs\Contracts\XmlDocumentLoader;
use App\Docs\Exceptions\InvalidXmlException;
use App\Docs\Support\LoadedDocument;
use App\Docs\Support\SourceFile;
use DOMDocument;
use LibXMLError;

/**
 * Loads XML into a {@see DOMDocument} with libxml's own error handling, hardened
 * against XXE by loading from a string with no network access.
 *
 * The PHP manual XML references hundreds of shared DocBook entities (e.g.
 * &reftitle.description;) that are defined in php/doc-base — which we do not use.
 * So when a strict parse fails purely on undefined entities, we synthesise an
 * internal DTD that declares each referenced entity as a readable placeholder
 * and re-parse. The declarations are internal and static, so this stays safe
 * (no external entity or DTD loading, no network).
 */
final class DomXmlDocumentLoader implements XmlDocumentLoader
{
    private const LIBXML_FLAGS = LIBXML_NONET | LIBXML_COMPACT | LIBXML_NOERROR | LIBXML_NOWARNING;

    /** XML's five predefined entities, which must never be redeclared. */
    private const PREDEFINED = ['amp', 'lt', 'gt', 'quot', 'apos'];

    public function load(SourceFile $file): LoadedDocument
    {
        if (! is_file($file->absolutePath) || ! is_readable($file->absolutePath)) {
            throw InvalidXmlException::forFile($file->absolutePath, ['file is missing or unreadable']);
        }

        $xml = file_get_contents($file->absolutePath);

        if ($xml === false || trim($xml) === '') {
            throw InvalidXmlException::forFile($file->absolutePath, ['file is empty or could not be read']);
        }

        [$document, $errors] = $this->parse($xml, self::LIBXML_FLAGS);

        if ($document === null) {
            $withEntities = $this->withDeclaredEntities($xml);
            if ($withEntities !== null) {
                [$document, $errors] = $this->parse($withEntities, self::LIBXML_FLAGS | LIBXML_NOENT);
            }
        }

        if ($document === null) {
            throw InvalidXmlException::forFile($file->absolutePath, $errors === [] ? ['unknown parse error'] : $errors);
        }

        return new LoadedDocument($file, $document);
    }

    /**
     * @return array{0: ?DOMDocument, 1: list<string>}
     */
    private function parse(string $xml, int $flags): array
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->substituteEntities = false;

        $loaded = $document->loadXML($xml, $flags);

        $errors = array_map(
            static fn (LibXMLError $error): string => trim($error->message),
            libxml_get_errors(),
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($loaded === false || $document->documentElement === null) {
            return [null, $errors];
        }

        return [$document, $errors];
    }

    /**
     * Build a copy of the XML with an internal DTD declaring every referenced
     * (non-predefined) entity, or null if there is nothing to declare / no root.
     */
    private function withDeclaredEntities(string $xml): ?string
    {
        preg_match_all('/&([A-Za-z_][A-Za-z0-9_.\-]*);/', $xml, $matches);
        $names = array_diff(array_unique($matches[1]), self::PREDEFINED);

        if ($names === []) {
            return null;
        }

        $root = $this->rootElementName($xml);
        if ($root === null) {
            return null;
        }

        $declarations = '';
        foreach ($names as $name) {
            $declarations .= '<!ENTITY '.$name.' "'.$this->entityValue($name).'">';
        }
        $doctype = "<!DOCTYPE {$root} [{$declarations}]>";

        $body = preg_replace('/<!DOCTYPE[^>\[]*(\[[\s\S]*?\])?\s*>/', '', $xml, 1) ?? $xml;

        if (preg_match('/^\s*<\?xml[^>]*\?>/', $body, $declaration) === 1) {
            return $declaration[0]."\n".$doctype.substr($body, strlen($declaration[0]));
        }

        return $doctype."\n".$body;
    }

    /**
     * A human-readable placeholder for an entity name, used as its replacement
     * text. Type-like entities keep their literal value; others become a label
     * derived from the last dotted segment.
     */
    /** Well-known shared entities whose generic label would read poorly. */
    private const KNOWN_LABELS = [
        'true' => 'true',
        'false' => 'false',
        'null' => 'null',
        'reftitle.description' => 'Description',
        'reftitle.parameters' => 'Parameters',
        'reftitle.returnvalues' => 'Return Values',
        'reftitle.errors' => 'Errors/Exceptions',
        'reftitle.examples' => 'Examples',
        'reftitle.notes' => 'Notes',
        'reftitle.changelog' => 'Changelog',
        'reftitle.seealso' => 'See Also',
        'reftitle.classsynopsis' => 'Class synopsis',
        'reftitle.constants' => 'Predefined Constants',
        'reftitle.properties' => 'Properties',
        'reftitle.unicodeversion' => 'Unicode Version',
        'example.outputs' => 'The above example will output:',
        'example.outputs.similar' => 'The above example will output something similar to:',
    ];

    private function entityValue(string $name): string
    {
        $key = strtolower($name);
        if (isset(self::KNOWN_LABELS[$key])) {
            return self::KNOWN_LABELS[$key];
        }

        $segment = str_contains($name, '.') ? (string) strrchr($name, '.') : $name;
        $segment = ltrim($segment, '.');
        $segment = preg_replace('/[^A-Za-z0-9 _-]/', '', $segment) ?? $segment;

        return $segment === '' ? $name : ucfirst($segment);
    }

    private function rootElementName(string $xml): ?string
    {
        $withoutComments = preg_replace('/<!--[\s\S]*?-->/', '', $xml) ?? $xml;

        if (preg_match('/<([A-Za-z_][A-Za-z0-9_:.\-]*)[\s>\/]/', $withoutComments, $match) === 1) {
            return $match[1];
        }

        return null;
    }
}
