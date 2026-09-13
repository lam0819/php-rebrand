<?php

declare(strict_types=1);

namespace App\Docs\Persistence;

use Illuminate\Database\Eloquent\Model;

/**
 * The Eloquent model backing the `docs_pages` table.
 *
 * Deliberately anaemic: it declares its schema mapping and nothing else. All
 * behaviour lives in services and the repository, never on the model.
 *
 * @property string $doc_id
 * @property string $slug
 * @property string $title
 * @property string $type
 * @property string $source_path
 * @property string|null $source_hash
 * @property string|null $raw_xml
 * @property string|null $content
 * @property string|null $body_html
 * @property array<string, mixed>|null $metadata
 */
final class DocPage extends Model
{
    protected $table = 'docs_pages';

    /** @var list<string> */
    protected $fillable = [
        'doc_id',
        'slug',
        'title',
        'type',
        'source_path',
        'source_hash',
        'raw_xml',
        'content',
        'body_html',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
