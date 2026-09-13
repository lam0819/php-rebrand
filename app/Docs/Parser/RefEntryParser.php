<?php

declare(strict_types=1);

namespace App\Docs\Parser;

use App\Docs\DTO\DocPageDTO;
use App\Docs\DTO\ExampleDTO;
use App\Docs\DTO\MetadataDTO;
use App\Docs\DTO\ParameterDTO;
use App\Docs\DTO\ReturnValueDTO;
use App\Docs\Enums\DocumentType;
use App\Docs\Exceptions\InvalidDocumentStructureException;
use App\Docs\Rendering\DocBookHtmlRenderer;
use App\Docs\Support\LoadedDocument;
use DOMElement;

/**
 * Parses DocBook <refentry> pages — the function/method reference pages that
 * make up the bulk of the PHP manual: name, purpose, signature, parameters,
 * return value, and examples.
 */
final class RefEntryParser extends AbstractDocumentParser
{
    protected function supportedTypes(): array
    {
        return [DocumentType::RefEntry];
    }

    protected function parseDocument(LoadedDocument $document): DocPageDTO
    {
        $dom = $document->document();
        $source = $document->source;

        $name = $this->firstText($dom, 'refname');
        if ($name === null || $name === '') {
            throw InvalidDocumentStructureException::missing('<refname>', $source->relativePath);
        }

        $purpose = $this->firstText($dom, 'refpurpose');
        [$signature, $parameters, $returnValue] = $this->parseSignature($dom);
        $examples = $this->parseExamples($dom);
        $version = $this->versionInfoFor($name);

        $content = trim(($purpose ?? '').($signature !== null ? "\n\n".$signature : ''));

        $bodyHtml = $dom->documentElement !== null
            ? (new DocBookHtmlRenderer)->render($dom->documentElement)
            : null;

        return new DocPageDTO(
            docId: $source->docId(),
            slug: $source->slug(),
            title: $name,
            type: DocumentType::RefEntry,
            sourcePath: $source->relativePath,
            metadata: new MetadataDTO(
                purpose: $purpose,
                version: $version?->from,
                extra: array_filter([
                    'signature' => $signature,
                    'deprecated' => $version?->deprecated,
                    'removed' => $version?->removed,
                ], static fn (?string $value): bool => $value !== null),
            ),
            rawXml: $document->rawXml(),
            content: $content,
            parameters: $parameters,
            returnValue: $returnValue,
            examples: $examples,
            bodyHtml: $bodyHtml,
        );
    }

    /**
     * @return array{0: ?string, 1: list<ParameterDTO>, 2: ?ReturnValueDTO}
     */
    private function parseSignature(\DOMNode $dom): array
    {
        $synopsis = $this->firstElement($dom, 'methodsynopsis');
        if ($synopsis === null) {
            return [null, [], null];
        }

        $methodName = $this->firstText($synopsis, 'methodname') ?? '';
        $returnType = $this->renderType($this->directChildElement($synopsis, 'type'));

        $parameters = [];
        $rendered = [];
        foreach ($this->elements($synopsis, 'methodparam') as $param) {
            $type = $this->renderType($this->firstElement($param, 'type')) ?: 'mixed';
            $pName = $this->firstText($param, 'parameter') ?? '';
            $optional = $param->getAttribute('choice') === 'opt';
            if ($pName === '') {
                continue;
            }
            $parameters[] = new ParameterDTO(name: $pName, type: $type, optional: $optional);
            $rendered[] = trim("{$type} \${$pName}");
        }

        $signature = trim("{$returnType} {$methodName}(".implode(', ', $rendered).')');
        $returnValue = $returnType === '' ? null : new ReturnValueDTO(type: $returnType);

        return [$signature, $parameters, $returnValue];
    }

    /**
     * @return list<ExampleDTO>
     */
    private function parseExamples(\DOMNode $dom): array
    {
        $examples = [];
        foreach ($this->elements($dom, 'example') as $i => $example) {
            $listing = $this->firstElement($example, 'programlisting');
            if ($listing === null) {
                continue;
            }
            $examples[] = new ExampleDTO(
                title: $this->firstText($example, 'title') ?? ('Example #'.($i + 1)),
                code: trim($listing->textContent),
                language: $listing->getAttribute('role') ?: 'php',
            );
        }

        return $examples;
    }

    /**
     * The first direct-child element with the given local name.
     */
    private function directChildElement(DOMElement $parent, string $localName): ?DOMElement
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === $localName) {
                return $child;
            }
        }

        return null;
    }

    /**
     * Render a <type> element, joining the members of a union type with "|"
     * (e.g. <type class="union"><type>string</type><type>array</type></type>).
     */
    private function renderType(?DOMElement $type): string
    {
        if ($type === null) {
            return '';
        }

        $members = [];
        foreach ($type->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === 'type') {
                $members[] = $this->text($child);
            }
        }

        return $members === [] ? $this->text($type) : implode('|', $members);
    }
}
