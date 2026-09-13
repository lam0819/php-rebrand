<?php

declare(strict_types=1);

namespace App\Web\DTO;

/**
 * Parsed representation of one active branch's current release from
 * php/web-php's `include/version.inc`.
 */
final readonly class ReleaseData
{
    /**
     * @param  array<int, string>  $tags
     * @param  array<string, string>  $sha256
     */
    public function __construct(
        public string $branch,
        public string $version,
        public string $date,
        public array $tags,
        public array $sha256,
    ) {}
}
