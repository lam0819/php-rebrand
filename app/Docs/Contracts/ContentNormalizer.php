<?php

declare(strict_types=1);

namespace App\Docs\Contracts;

use App\Docs\DTO\DocPageDTO;

/**
 * Cleans and standardises a parsed document before persistence — collapsing
 * whitespace, trimming, and similar source-agnostic tidying.
 *
 * Normalizers are pure: they accept a DTO and return a new, normalized DTO
 * without mutating the input.
 */
interface ContentNormalizer
{
    public function normalize(DocPageDTO $page): DocPageDTO;
}
