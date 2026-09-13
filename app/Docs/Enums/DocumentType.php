<?php

declare(strict_types=1);

namespace App\Docs\Enums;

/**
 * The DocBook document types we recognise from the PHP documentation source.
 *
 * The backing value is the XML root element name (e.g. <refentry>, <chapter>),
 * which is how a document is classified without parsing its contents.
 */
enum DocumentType: string
{
    case RefEntry = 'refentry';
    case Chapter = 'chapter';
    case Article = 'article';
    case Appendix = 'appendix';
    case Part = 'part';
    case Preface = 'preface';
    case Reference = 'reference';
    case Set = 'set';
    case Book = 'book';
    case Section = 'section';
    case Sect1 = 'sect1';
    case Sect2 = 'sect2';
    case Sect3 = 'sect3';
    case Unknown = 'unknown';

    /**
     * Resolve a document type from an XML root element name.
     *
     * Unknown or unsupported root elements fall back to {@see self::Unknown}
     * rather than throwing, so classification is always total.
     */
    public static function fromRootElement(string $rootElement): self
    {
        return self::tryFrom(strtolower(trim($rootElement))) ?? self::Unknown;
    }

    /**
     * Whether this type is a recognised, importable document type.
     */
    public function isKnown(): bool
    {
        return $this !== self::Unknown;
    }

    /**
     * A human-friendly label for the type.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }
}
