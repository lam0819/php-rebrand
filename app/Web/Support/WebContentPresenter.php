<?php

declare(strict_types=1);

namespace App\Web\Support;

use App\Web\Models\NewsItem;
use App\Web\Models\PhpRelease;
use Illuminate\Support\Collection;

/**
 * Shapes the imported news + release models into the small arrays the Blade
 * marketing views consume (pills, tags, human dates).
 */
final class WebContentPresenter
{
    /**
     * The current release of each active branch, newest first, decorated with a
     * support pill. The first (newest) branch is the recommended one.
     *
     * @param  Collection<int, PhpRelease>  $releases
     * @return list<array{version: string, branch: string, pill: string, tag: string, note: string, security: bool, sha256: string|null}>
     */
    public function releaseCards(Collection $releases): array
    {
        $ordered = $releases->sortByDesc('branch')->values();

        return array_values($ordered->map(function (PhpRelease $release, int $index): array {
            $date = $release->released_on?->format('d M Y');

            return [
                'version' => $release->version,
                'branch' => $release->branch,
                'pill' => $index === 0 ? 'pill-ok' : ($release->isSecurityRelease() ? 'pill-warn' : 'pill-ok'),
                'tag' => $index === 0 ? 'Current' : ($release->isSecurityRelease() ? 'Security' : 'Active'),
                'note' => $date !== null ? "Released {$date}." : '',
                'security' => $release->isSecurityRelease(),
                'sha256' => $release->sha256['tar.gz'] ?? null,
            ];
        })->all());
    }

    /**
     * A short display tag + pill colour for a news entry, derived from its
     * primary Atom category term.
     *
     * @return array{pill: string, tag: string}
     */
    public function newsTag(NewsItem $item): array
    {
        return match ($item->category) {
            'releases' => ['pill' => 'pill-ok', 'tag' => 'Release'],
            'security' => ['pill' => 'pill-danger', 'tag' => 'Security'],
            'cfp' => ['pill' => 'pill-warn', 'tag' => 'Call for Papers'],
            'conferences' => ['pill' => 'pill-warn', 'tag' => 'Event'],
            default => ['pill' => 'pill-accent', 'tag' => $item->label ?? 'News'],
        };
    }

    /**
     * A single changelog entry rendered as safe HTML, with bug/CVE references
     * linked to their trackers (GH-, #bug, GHSA-, CVE-). The text is escaped
     * with ENT_HTML5 so apostrophes become &apos; rather than &#039; — keeping
     * the "#1234" bug pattern from colliding with numeric entities.
     */
    public function changelogEntry(string $text): string
    {
        $html = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $link = static fn (string $href, string $label): string => '<a class="accent" href="'.$href.'" target="_blank" rel="noreferrer">'.$label.'</a>';

        $html = (string) preg_replace_callback('/\bGHSA-[0-9a-z]{4}-[0-9a-z]{4}-[0-9a-z]{4}\b/', static fn (array $m): string => $link('https://github.com/php/php-src/security/advisories/'.$m[0], $m[0]), $html);
        $html = (string) preg_replace_callback('/\bGH-(\d+)\b/', static fn (array $m): string => $link('https://github.com/php/php-src/issues/'.$m[1], $m[0]), $html);
        $html = (string) preg_replace_callback('/\bCVE-\d+-\d+\b/', static fn (array $m): string => $link('https://www.cve.org/CVERecord?id='.$m[0], $m[0]), $html);
        $html = (string) preg_replace_callback('/(?<![\w])#(\d+)\b/', static fn (array $m): string => $link('https://bugs.php.net/'.$m[1], $m[0]), $html);

        return $html;
    }
}
