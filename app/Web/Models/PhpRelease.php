<?php

declare(strict_types=1);

namespace App\Web\Models;

use Database\Factories\PhpReleaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The current release of an active PHP branch, mirrored from php/web-php's
 * `include/version.inc` `$RELEASES` config.
 *
 * @property int $id
 * @property string $branch
 * @property string $version
 * @property \Illuminate\Support\Carbon|null $released_on
 * @property array<int, string> $tags
 * @property array<string, string> $sha256
 * @property string|null $source_hash
 */
final class PhpRelease extends Model
{
    /** @use HasFactory<PhpReleaseFactory> */
    use HasFactory;

    protected $fillable = [
        'branch',
        'version',
        'released_on',
        'tags',
        'sha256',
        'source_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'released_on' => 'date',
            'tags' => 'array',
            'sha256' => 'array',
        ];
    }

    protected static function newFactory(): PhpReleaseFactory
    {
        return PhpReleaseFactory::new();
    }

    public function isSecurityRelease(): bool
    {
        return in_array('security', $this->tags, true);
    }

    public function downloadUrl(): string
    {
        return 'https://www.php.net/distributions/php-'.$this->version.'.tar.gz';
    }
}
