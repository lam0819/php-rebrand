<?php

declare(strict_types=1);

namespace App\Docs\DTO;

/**
 * A single parameter of a function or method reference.
 */
final readonly class ParameterDTO
{
    public function __construct(
        public string $name,
        public string $type,
        public string $description = '',
        public bool $optional = false,
        public ?string $default = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'optional' => $this->optional,
            'default' => $this->default,
        ];
    }
}
