<?php

declare(strict_types=1);

namespace App\Docs\DTO;

/**
 * A runnable code example with an optional expected output.
 */
final readonly class ExampleDTO
{
    public function __construct(
        public string $title,
        public string $code,
        public string $language = 'php',
        public ?string $output = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'code' => $this->code,
            'language' => $this->language,
            'output' => $this->output,
        ];
    }
}
