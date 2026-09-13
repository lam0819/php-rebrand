<?php

declare(strict_types=1);

namespace App\Docs\DTO;

/**
 * Free-form, plain-value metadata extracted from a document.
 *
 * Holds the commonly-needed fields explicitly and keeps everything else in
 * {@see self::$extra}. Contains only scalar/array values — never DOM nodes.
 */
final readonly class MetadataDTO
{
    /**
     * @param  array<string, scalar|array<mixed>|null>  $extra
     */
    public function __construct(
        public ?string $purpose = null,
        public ?string $version = null,
        public array $extra = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'purpose' => $this->purpose,
            'version' => $this->version,
            'extra' => $this->extra === [] ? null : $this->extra,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
