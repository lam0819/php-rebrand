<?php

declare(strict_types=1);

use App\Docs\Enums\DocumentType;

it('maps root element names to types case-insensitively', function () {
    expect(DocumentType::fromRootElement('refentry'))->toBe(DocumentType::RefEntry)
        ->and(DocumentType::fromRootElement('  CHAPTER '))->toBe(DocumentType::Chapter)
        ->and(DocumentType::fromRootElement('article'))->toBe(DocumentType::Article);
});

it('falls back to Unknown for unrecognised elements', function () {
    expect(DocumentType::fromRootElement('colophon'))->toBe(DocumentType::Unknown)
        ->and(DocumentType::Unknown->isKnown())->toBeFalse()
        ->and(DocumentType::RefEntry->isKnown())->toBeTrue();
});
