<?php

declare(strict_types=1);

namespace App\Docs\Support;

use App\Docs\Persistence\DocPage;

/**
 * Turns a persisted {@see DocPage} into the plain array shapes the read layer
 * renders — the full page view and the compact list/search summary.
 *
 * Centralising this here keeps the (nested) metadata access in one tested place
 * so controllers, Livewire components and the JSON API never re-derive it.
 */
final class DocPagePresenter
{
    /**
     * The full, render-ready representation of a single manual page.
     *
     * @return array{
     *     slug: string, title: string, type: string, sourcePath: string,
     *     content: ?string, bodyHtml: ?string, purpose: ?string, signature: ?string,
     *     version: ?string, deprecated: ?string, removed: ?string,
     *     parameters: list<mixed>, returnValue: mixed, examples: list<mixed>,
     *     sections: list<mixed>, seeAlso: list<mixed>
     * }
     */
    public function present(DocPage $page): array
    {
        $metadata = is_array($page->metadata) ? $page->metadata : [];
        $inner = $this->subArray($metadata, 'metadata');
        $extra = $this->subArray($inner, 'extra');
        $signature = isset($extra['signature']) && is_string($extra['signature']) ? $extra['signature'] : null;

        return [
            'slug' => $page->slug,
            'title' => $page->title,
            'type' => $page->type,
            'sourcePath' => $page->source_path,
            'content' => $page->content,
            'bodyHtml' => $page->body_html,
            'purpose' => $this->purpose($page),
            'signature' => $signature,
            'version' => isset($inner['version']) && is_string($inner['version']) ? $inner['version'] : null,
            'deprecated' => isset($extra['deprecated']) && is_string($extra['deprecated']) ? $extra['deprecated'] : null,
            'removed' => isset($extra['removed']) && is_string($extra['removed']) ? $extra['removed'] : null,
            'parameters' => $this->list($metadata, 'parameters'),
            'returnValue' => $metadata['returnValue'] ?? null,
            'examples' => $this->list($metadata, 'examples'),
            'sections' => $this->list($metadata, 'sections'),
            'seeAlso' => $this->list($metadata, 'seeAlso'),
        ];
    }

    /**
     * The compact representation used in lists and search results.
     *
     * @return array{slug: string, name: string, kind: string, desc: string}
     */
    public function summary(DocPage $page): array
    {
        return [
            'slug' => $page->slug,
            'name' => $page->title,
            'kind' => $page->type,
            'desc' => $this->purpose($page) ?? '',
        ];
    }

    public function purpose(DocPage $page): ?string
    {
        $metadata = is_array($page->metadata) ? $page->metadata : [];
        $inner = $this->subArray($metadata, 'metadata');
        $purpose = $inner['purpose'] ?? null;

        return is_string($purpose) ? $purpose : null;
    }

    /**
     * The page's real prose as plain text. `content` only holds a short summary
     * (often just the signature line); the manual body lives in `body_html`, so
     * search indexing and AI context must read from there.
     */
    public function plainText(DocPage $page, int $limit = 0): string
    {
        $html = $page->body_html ?: $page->content ?: '';
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return $limit > 0 ? mb_substr($text, 0, $limit) : $text;
    }

    /**
     * @param  array<array-key, mixed>  $array
     * @return list<mixed>
     */
    private function list(array $array, string $key): array
    {
        return isset($array[$key]) && is_array($array[$key]) ? array_values($array[$key]) : [];
    }

    /**
     * @param  array<array-key, mixed>  $array
     * @return array<array-key, mixed>
     */
    private function subArray(array $array, string $key): array
    {
        return isset($array[$key]) && is_array($array[$key]) ? $array[$key] : [];
    }
}
