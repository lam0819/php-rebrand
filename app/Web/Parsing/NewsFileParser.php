<?php

declare(strict_types=1);

namespace App\Web\Parsing;

use App\Web\DTO\ChangelogReleaseData;
use DateTimeImmutable;

/**
 * Parses a php/php-src NEWS file into per-release changelog data.
 *
 * The grammar mirrors php/web-php's own `bin/news2html`:
 *   - A release starts with a header line "DD Mon YYYY, PHP X.Y.Z"
 *     (or "?? ??? ????, PHP X.Y.Z" for an in-development version).
 *   - "- Category:" begins a category block.
 *   - "  . text" begins a bullet entry; deeper-indented lines continue it.
 *   - The trailing " (author)" attribution is stripped (CVE refs are kept),
 *     matching what php.net's ChangeLog pages show.
 */
final class NewsFileParser
{
    private const HEADER = '/^(\?\? \?\?\? \?\?\?\?|\d{1,2} \w{3} \d{4}),?\s+PHP\s+(\d+\.\d+\.\d+\S*)\s*$/m';

    /**
     * @return array<int, ChangelogReleaseData>
     */
    public function parse(string $news): array
    {
        if (preg_match_all(self::HEADER, $news, $headers, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) === 0) {
            return [];
        }

        $releases = [];
        $count = count($headers);
        for ($i = 0; $i < $count; $i++) {
            $bodyStart = (int) $headers[$i][0][1] + strlen((string) $headers[$i][0][0]);
            $bodyEnd = $i + 1 < $count ? (int) $headers[$i + 1][0][1] : strlen($news);

            $releases[] = $this->parseBlock(
                date: (string) $headers[$i][1][0],
                version: (string) $headers[$i][2][0],
                body: substr($news, $bodyStart, $bodyEnd - $bodyStart),
            );
        }

        return $releases;
    }

    private function parseBlock(string $date, string $version, string $body): ChangelogReleaseData
    {
        /** @var array<int, string> $order */
        $order = [];
        /** @var array<string, array<int, string>> $byCategory */
        $byCategory = [];
        $category = null;
        $buffer = [];     // accumulated lines of the current bullet

        foreach (preg_split('/\r\n|\n|\r/', $body) ?: [] as $line) {
            $isCategory = preg_match('/^-\s*(.+?):\s*$/', $line, $cm) === 1;
            $isEntry = preg_match('/^\s+\.\s+(.*)$/', $line, $em) === 1;
            $isBlank = trim($line) === '';

            // A bullet ends at the next category, the next bullet, or a blank line.
            if ($isCategory || $isEntry || $isBlank) {
                if ($category !== null && $buffer !== []) {
                    $text = $this->tidy(implode(' ', $buffer));
                    if ($text !== '') {
                        $byCategory[$category][] = $text;
                    }
                }
                $buffer = [];
            }

            if ($isCategory) {
                $category = trim($cm[1]);
                if (! isset($byCategory[$category])) {
                    $byCategory[$category] = [];
                    $order[] = $category;
                }
            } elseif ($isEntry) {
                $buffer = [trim($em[1])];
            } elseif (! $isBlank && $buffer !== [] && preg_match('/^\s+\S/', $line) === 1) {
                $buffer[] = trim($line); // continuation of the current bullet
            }
        }

        if ($category !== null && $buffer !== []) {
            $text = $this->tidy(implode(' ', $buffer));
            if ($text !== '') {
                $byCategory[$category][] = $text;
            }
        }

        $sections = [];
        foreach ($order as $name) {
            if ($byCategory[$name] !== []) {
                $sections[] = ['category' => $name, 'entries' => array_values($byCategory[$name])];
            }
        }

        $released = ! str_starts_with($date, '??');
        [$major, $minor] = explode('.', $version);

        return new ChangelogReleaseData(
            version: $version,
            branch: $major.'.'.$minor,
            date: $released ? (DateTimeImmutable::createFromFormat('d M Y', $date) ?: null) : null,
            released: $released,
            sections: $sections,
        );
    }

    /**
     * Strip the trailing " (author)" attribution while keeping a "(CVE-…)" ref,
     * exactly as php/web-php's news2html does.
     */
    private function tidy(string $entry): string
    {
        $entry = (string) preg_replace('/\s+/', ' ', trim($entry));

        return (string) preg_replace('/(\.(\s+\(CVE-\d+-\d+\))?)\s+\(.+?\)\s*$/', '$1', $entry);
    }
}
