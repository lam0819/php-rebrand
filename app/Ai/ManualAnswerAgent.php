<?php

declare(strict_types=1);

namespace App\Ai;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

/**
 * Answers a question strictly from the manual pages retrieved for it. The
 * numbered context is injected as system instructions; the user prompt is just
 * the question. The model replies with Markdown that cites pages inline as
 * [1], [2] — those indices are mapped back to our own URLs after generation,
 * so the model never authors a link.
 */
final class ManualAnswerAgent implements Agent
{
    use Promptable;

    public function __construct(private readonly string $context) {}

    public function instructions(): string
    {
        $rules = <<<'PROMPT'
        You are the assistant embedded in a modern PHP manual website. Answer the
        question using ONLY the numbered reference pages below.

        - Write a concise, friendly answer in Markdown. Explain in your own words.
        - Cite the pages you use inline as bracketed numbers — e.g. [1] or [2][3].
          Never invent a number that is not in the list.
        - Never invent functions, classes, pages, URLs, versions, or behaviour.
        - If the pages do not contain the answer, say so plainly and suggest a better search term.
        - Spell the exact function or class name the user asked about correctly.
        - Do not wrap the answer in a JSON object; reply with the Markdown text only.
        PROMPT;

        return $rules."\n\nReference pages:\n\n".$this->context;
    }
}
