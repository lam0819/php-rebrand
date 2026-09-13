<?php

declare(strict_types=1);

namespace App\Docs\DTO;

/**
 * A titled block of prose within a document (e.g. "Description", "Notes").
 */
final readonly class SectionDTO
{
    public function __construct(
        public string $id,
        public string $title,
        public string $content,
        public int $level = 2,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'level' => $this->level,
        ];
    }
}
