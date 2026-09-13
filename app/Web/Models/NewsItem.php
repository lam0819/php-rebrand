<?php

declare(strict_types=1);

namespace App\Web\Models;

use Database\Factories\NewsItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A single news/announcement entry mirrored from php/web-php's
 * `archive/entries/*.xml` Atom archive.
 *
 * @property int $id
 * @property string $entry_id
 * @property string $title
 * @property string|null $category
 * @property string|null $label
 * @property array<int, array{term: string, label: string}> $terms
 * @property string $body_html
 * @property string|null $link
 * @property string|null $via
 * @property \Illuminate\Support\Carbon $published_at
 * @property string|null $source_hash
 */
final class NewsItem extends Model
{
    /** @use HasFactory<NewsItemFactory> */
    use HasFactory;

    protected $fillable = [
        'entry_id',
        'title',
        'category',
        'label',
        'terms',
        'body_html',
        'link',
        'via',
        'published_at',
        'source_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'terms' => 'array',
            'published_at' => 'datetime',
        ];
    }

    protected static function newFactory(): NewsItemFactory
    {
        return NewsItemFactory::new();
    }
}
