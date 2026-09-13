<?php

declare(strict_types=1);

namespace App\Web\Models;

use Database\Factories\ChangelogReleaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One PHP release's changelog, mirrored from the per-branch NEWS file in
 * php/php-src (the source php.net's ChangeLog-N.php pages are built from).
 *
 * @property int $id
 * @property string $version
 * @property string $branch
 * @property \Illuminate\Support\Carbon|null $released_on
 * @property bool $released
 * @property array<int, array{category: string, entries: array<int, string>}> $sections
 * @property int $entry_count
 * @property string|null $source_hash
 */
final class ChangelogRelease extends Model
{
    /** @use HasFactory<ChangelogReleaseFactory> */
    use HasFactory;

    protected $fillable = [
        'version',
        'branch',
        'released_on',
        'released',
        'sections',
        'entry_count',
        'source_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'released_on' => 'date',
            'released' => 'boolean',
            'sections' => 'array',
        ];
    }

    protected static function newFactory(): ChangelogReleaseFactory
    {
        return ChangelogReleaseFactory::new();
    }
}
