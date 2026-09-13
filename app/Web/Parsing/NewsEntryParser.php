<?php

declare(strict_types=1);

namespace App\Web\Parsing;

use App\Web\DTO\NewsEntryData;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use RuntimeException;

/**
 * Parses a single Atom `<entry>` file from php/web-php's `archive/entries/`
 * directory into a {@see NewsEntryData}.
 *
 * Each file is one Atom entry in the `http://www.w3.org/2005/Atom` namespace;
 * the body lives in `<content type="xhtml">` wrapped in an XHTML `<div>`.
 */
final class NewsEntryParser
{
    private const ATOM = 'http://www.w3.org/2005/Atom';

    public function parse(string $xml, string $entryId): NewsEntryData
    {
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded || $dom->documentElement === null) {
            throw new RuntimeException("Unable to parse news entry [{$entryId}].");
        }

        $entry = $dom->documentElement;

        $terms = $this->terms($entry);

        return new NewsEntryData(
            entryId: $entryId,
            title: $this->text($entry, 'title'),
            publishedAt: $this->publishedAt($entry),
            bodyHtml: $this->content($entry),
            terms: $terms,
            category: $terms[0]['term'] ?? null,
            label: $terms[0]['label'] ?? null,
            link: $this->link($entry, 'alternate'),
            via: $this->link($entry, 'via'),
        );
    }

    private function text(DOMElement $entry, string $tag): string
    {
        $node = $entry->getElementsByTagNameNS(self::ATOM, $tag)->item(0);

        return $node === null ? '' : trim($node->textContent);
    }

    private function publishedAt(DOMElement $entry): DateTimeImmutable
    {
        $raw = $this->text($entry, 'published') ?: $this->text($entry, 'updated');
        $date = $raw === '' ? false : DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, $raw);

        return $date === false ? new DateTimeImmutable('@0') : $date;
    }

    /**
     * @return array<int, array{term: string, label: string}>
     */
    private function terms(DOMElement $entry): array
    {
        $terms = [];
        foreach ($entry->getElementsByTagNameNS(self::ATOM, 'category') as $category) {
            $term = $category->getAttribute('term');
            if ($term === '') {
                continue;
            }
            $terms[] = ['term' => $term, 'label' => $category->getAttribute('label') ?: $term];
        }

        return $terms;
    }

    private function link(DOMElement $entry, string $rel): ?string
    {
        foreach ($entry->getElementsByTagNameNS(self::ATOM, 'link') as $link) {
            if ($link->getAttribute('rel') === $rel) {
                $href = $link->getAttribute('href');

                return $href === '' ? null : $href;
            }
        }

        return null;
    }

    /**
     * Returns the inner XHTML of `<content type="xhtml">` (the wrapping `<div>`
     * is unwrapped). Links pointing at php.net are rewritten to local paths so
     * the news reads as part of this site.
     */
    private function content(DOMElement $entry): string
    {
        $content = $entry->getElementsByTagNameNS(self::ATOM, 'content')->item(0);
        if (! $content instanceof DOMElement) {
            return '';
        }

        $html = '';
        foreach ($content->childNodes as $child) {
            // Unwrap the single XHTML <div> wrapper, keeping its inner markup.
            if ($child instanceof DOMElement && $child->localName === 'div') {
                foreach ($child->childNodes as $inner) {
                    $html .= $this->serialize($inner);
                }
            } else {
                $html .= $this->serialize($child);
            }
        }

        return trim($html);
    }

    private function serialize(\DOMNode $node): string
    {
        $doc = $node->ownerDocument;

        return $doc === null ? '' : (string) $doc->saveHTML($node);
    }
}
