<?php

declare(strict_types=1);

namespace App\Web\DTO;

use DateTimeImmutable;

/**
 * Parsed representation of one Atom news entry from php/web-php.
 */
final readonly class NewsEntryData
{
    /**
     * @param  array<int, array{term: string, label: string}>  $terms
     */
    public function __construct(
        public string $entryId,
        public string $title,
        public DateTimeImmutable $publishedAt,
        public string $bodyHtml,
        public array $terms,
        public ?string $category = null,
        public ?string $label = null,
        public ?string $link = null,
        public ?string $via = null,
    ) {}
}
