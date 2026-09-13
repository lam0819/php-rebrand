<?php

declare(strict_types=1);

namespace App\Docs\Normalizer;

use App\Docs\Contracts\ContentNormalizer;
use App\Docs\DTO\DocPageDTO;

/**
 * Source-agnostic tidying applied to every parsed page before persistence:
 * collapses runs of whitespace and trims the title and body content.
 *
 * Returns a new DTO; the input is never mutated.
 */
final class DefaultContentNormalizer implements ContentNormalizer
{
    public function normalize(DocPageDTO $page): DocPageDTO
    {
        return $page
            ->withTitle($this->collapse($page->title))
            ->withContent($page->content === null ? null : $this->collapse($page->content));
    }

    private function collapse(string $value): string
    {
        $collapsed = preg_replace('/[ \t]+/', ' ', $value) ?? $value;
        $collapsed = preg_replace('/\s*\n\s*\n\s*/', "\n\n", $collapsed) ?? $collapsed;

        return trim($collapsed);
    }
}
