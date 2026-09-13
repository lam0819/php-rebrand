<?php

declare(strict_types=1);

namespace App\Docs\DTO;

/**
 * A cross-reference to another document, function, or external resource.
 */
final readonly class SeeAlsoDTO
{
    public function __construct(
        public string $name,
        public ?string $url = null,
        public string $kind = 'reference',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'url' => $this->url,
            'kind' => $this->kind,
        ];
    }
}
