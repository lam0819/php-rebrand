<?php

declare(strict_types=1);

namespace App\Web\DTO;

use DateTimeImmutable;

/**
 * Parsed representation of one release section in a php/php-src NEWS file.
 */
final readonly class ChangelogReleaseData
{
    /**
     * @param  array<int, array{category: string, entries: array<int, string>}>  $sections
     */
    public function __construct(
        public string $version,
        public string $branch,
        public ?DateTimeImmutable $date,
        public bool $released,
        public array $sections,
    ) {}

    public function entryCount(): int
    {
        return array_sum(array_map(static fn (array $section): int => count($section['entries']), $this->sections));
    }
}
