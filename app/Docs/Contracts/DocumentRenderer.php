<?php

declare(strict_types=1);

namespace App\Docs\Contracts;

use App\Docs\DTO\DocPageDTO;

/**
 * Renders a canonical document into a target representation.
 *
 * No renderer is implemented in this PR. The contract is defined now so that
 * Markdown, HTML, JSON, and LLM-context renderers can be added later as drop-in
 * implementations resolved by format — without touching the import pipeline.
 */
interface DocumentRenderer
{
    /**
     * The format this renderer produces (e.g. "markdown", "html", "json").
     */
    public function format(): string;

    public function render(DocPageDTO $page): string;
}
