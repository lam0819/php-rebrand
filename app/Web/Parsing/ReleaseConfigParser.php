<?php

declare(strict_types=1);

namespace App\Web\Parsing;

use App\Web\DTO\ReleaseData;

/**
 * Parses the `$RELEASES` config block from php/web-php's `include/version.inc`.
 *
 * We deliberately parse the source text rather than `include` it: the file
 * defines functions and depends on other includes, so evaluating it is unsafe.
 * The `$data['x.y'] = [ … ];` blocks are hand-maintained in a stable shape, so
 * a focused scan is reliable and side-effect free.
 */
final class ReleaseConfigParser
{
    /**
     * @return array<int, ReleaseData>
     */
    public function parse(string $source): array
    {
        // Only look at the authored config, before the IIFE's transform section.
        $cut = strpos($source, '$ret = [];');
        if ($cut !== false) {
            $source = substr($source, 0, $cut);
        }

        // Each block: $data['8.5'] = [ … ];  (the first "];" terminates it).
        preg_match_all(
            "/\\\$data\\['(?<branch>[^']+)'\\]\\s*=\\s*\\[(?<body>.*?)\\];/s",
            $source,
            $matches,
            PREG_SET_ORDER,
        );

        $releases = [];
        foreach ($matches as $match) {
            $release = $this->parseBlock($match['branch'], $match['body']);
            if ($release instanceof ReleaseData) {
                $releases[] = $release;
            }
        }

        return $releases;
    }

    private function parseBlock(string $branch, string $body): ?ReleaseData
    {
        if (! preg_match("/'version'\\s*=>\\s*'([^']+)'/", $body, $version)) {
            return null;
        }

        preg_match("/'date'\\s*=>\\s*'([^']+)'/", $body, $date);

        return new ReleaseData(
            branch: $branch,
            version: $version[1],
            date: $date[1] ?? '',
            tags: $this->tags($body),
            sha256: $this->sha256($body),
        );
    }

    /**
     * @return array<int, string>
     */
    private function tags(string $body): array
    {
        if (! preg_match("/'tags'\\s*=>\\s*\\[([^\\]]*)\\]/", $body, $match)) {
            return [];
        }

        preg_match_all("/'([^']+)'/", $match[1], $tags);

        return $tags[1];
    }

    /**
     * @return array<string, string>
     */
    private function sha256(string $body): array
    {
        $start = strpos($body, "'sha256'");
        $segment = $start === false ? $body : substr($body, $start);

        preg_match_all("/'(tar\\.[a-z0-9]+)'\\s*=>\\s*'([0-9a-f]{64})'/", $segment, $matches, PREG_SET_ORDER);

        $hashes = [];
        foreach ($matches as $match) {
            $hashes[$match[1]] = $match[2];
        }

        return $hashes;
    }
}
