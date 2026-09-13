<?php

declare(strict_types=1);

namespace App\Docs\DTO;

/**
 * The return type and description of a function or method reference.
 */
final readonly class ReturnValueDTO
{
    public function __construct(
        public string $type,
        public string $description = '',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'description' => $this->description,
        ];
    }
}
