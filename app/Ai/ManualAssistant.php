<?php

declare(strict_types=1);

namespace App\Ai;

use Laravel\Ai\Streaming\Events\TextDelta;
use RuntimeException;
use Throwable;

/**
 * Runs the RAG answer across the configured OpenRouter models, in order. A
 * model is abandoned if it does not emit its first token within
 * `assistant.first_token_timeout` seconds, or if the request errors — so an
 * unresponsive model can never hang the request until the gateway 504s.
 */
final class ManualAssistant
{
    /**
     * @return array{text: string, model: string}
     */
    public function answer(string $question, string $context): array
    {
        $models = array_values((array) config('assistant.models'));
        $threshold = (float) config('assistant.first_token_timeout');
        $total = (int) config('assistant.total_timeout');
        $deadline = microtime(true) + (float) config('assistant.deadline');
        $lastError = null;

        foreach ($models as $model) {
            $remaining = $deadline - microtime(true);

            if ($remaining < 1) {
                break;
            }

            $started = microtime(true);

            try {
                $stream = (new ManualAnswerAgent($context))->stream(
                    $question,
                    provider: config('assistant.provider'),
                    model: $model,
                    timeout: (int) max(1, min($total, $remaining)),
                );

                $text = '';
                $firstTokenAt = null;

                foreach ($stream as $event) {
                    if (! $event instanceof TextDelta) {
                        continue;
                    }

                    if ($firstTokenAt === null) {
                        $firstTokenAt = microtime(true) - $started;

                        if ($firstTokenAt > $threshold) {
                            break; // Too slow to start — try the next model.
                        }
                    }

                    $text .= $event->delta;
                }

                if ($firstTokenAt === null || trim($text) === '') {
                    $lastError = new RuntimeException("Model [{$model}] did not answer in time.");

                    continue;
                }

                return ['text' => trim($text), 'model' => $model];
            } catch (Throwable $exception) {
                $lastError = $exception;
            }
        }

        throw $lastError ?? new RuntimeException('No assistant model could answer.');
    }
}
