<?php

declare(strict_types=1);

namespace App\Ai;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Answers a question strictly from the manual pages retrieved for it. The
 * numbered context is injected as system instructions; the user prompt is just
 * the question. Structured output keeps the answer and its citations separate,
 * so links are built from our own page list — never from model-authored URLs.
 */
final class ManualAnswerAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(private readonly string $context) {}

    public function instructions(): string
    {
        $rules = <<<'PROMPT'
        You are the assistant embedded in a modern PHP manual website. Answer the
        question using ONLY the numbered reference pages below.

        - Be concise and friendly, in Markdown. Explain in your own words; never dump an excerpt.
        - Whenever you rely on a page, cite it inline with its bracketed number — e.g. [1] or [2][3].
        - Never invent functions, classes, pages, URLs, versions, or behaviour.
        - If the pages do not contain the answer, say so plainly and suggest a better search term.
        - Spell the exact function or class name the user asked about correctly.
        PROMPT;

        return $rules."\n\nReference pages:\n\n".$this->context;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'answer' => $schema->string()->required(),
            'citations' => $schema->array()->items($schema->integer())->required(),
        ];
    }
}
