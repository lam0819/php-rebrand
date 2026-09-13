<?php

declare(strict_types=1);

namespace App\Docs\Rendering;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Converts the rendered manual body HTML (produced by {@see DocBookHtmlRenderer},
 * a small fixed tag set) into clean Markdown for AI agents and the `.md` views.
 */
final class HtmlToMarkdown
{
    public function convert(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="__root">'.$html.'</div>',
            LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('__root');
        $markdown = $root === null ? '' : $this->blocks($root);

        // Collapse 3+ blank lines to a single blank line.
        return trim((string) preg_replace("/\n{3,}/", "\n\n", $markdown))."\n";
    }

    private function blocks(DOMNode $node): string
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            $out .= $this->block($child);
        }

        return $out;
    }

    private function block(DOMNode $node): string
    {
        if ($node instanceof DOMText) {
            return '';
        }
        if (! $node instanceof DOMElement) {
            return '';
        }

        $class = $node->getAttribute('class');

        return match (true) {
            $node->localName === 'h2' => "\n## ".$this->inline($node)."\n\n",
            $node->localName === 'h3' => "\n### ".$this->inline($node)."\n\n",
            $node->localName === 'h4' => "\n#### ".$this->inline($node)."\n\n",
            $node->localName === 'div' && str_contains($class, 'sig') => '`'.trim($node->textContent)."`\n\n",
            $node->localName === 'div' && str_contains($class, 'code') => $this->code($node),
            $node->localName === 'div' && str_contains($class, 'note') => $this->note($node),
            $node->localName === 'p' => $this->inline($node)."\n\n",
            $node->localName === 'ul' => $this->list($node, '- '),
            $node->localName === 'ol' => $this->list($node, '1. '),
            $node->localName === 'dl' => $this->definitions($node),
            $node->localName === 'table' => $this->table($node),
            $node->localName === 'blockquote' => '> '.trim($this->inline($node))."\n\n",
            default => $this->blocks($node),
        };
    }

    private function code(DOMElement $node): string
    {
        $lang = '';
        foreach ($node->getElementsByTagName('span') as $span) {
            if (str_contains($span->getAttribute('class'), 'fname')) {
                $lang = trim($span->textContent);
                break;
            }
        }
        $code = '';
        $pre = $node->getElementsByTagName('code')->item(0);
        if ($pre !== null) {
            $code = trim($pre->textContent);
        }
        $lang = $lang === 'output' ? '' : $lang;

        return "```".$lang."\n".$code."\n```\n\n";
    }

    private function note(DOMElement $node): string
    {
        $label = 'Note';
        $body = '';
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && str_contains($child->getAttribute('class'), 'note-label')) {
                $label = trim($child->textContent);

                continue;
            }
            $body .= $this->block($child);
        }

        $lines = array_values(array_filter(array_map('trim', explode("\n", $body))));

        return '> **'.$label.':** '.implode("\n> ", $lines)."\n\n";
    }

    private function list(DOMElement $node, string $marker): string
    {
        $out = '';
        foreach ($node->childNodes as $li) {
            if ($li instanceof DOMElement && $li->localName === 'li') {
                $out .= $marker.trim($this->inline($li))."\n";
            }
        }

        return $out."\n";
    }

    private function definitions(DOMElement $node): string
    {
        $out = '';
        $term = '';
        foreach ($node->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }
            if ($child->localName === 'dt') {
                $term = trim($this->inline($child));
            } elseif ($child->localName === 'dd') {
                $out .= '- **'.$term.'** — '.trim($this->inline($child))."\n";
            }
        }

        return $out."\n";
    }

    private function table(DOMElement $node): string
    {
        $rows = [];
        foreach ($node->getElementsByTagName('tr') as $tr) {
            $cells = [];
            foreach ($tr->childNodes as $cell) {
                if ($cell instanceof DOMElement && in_array($cell->localName, ['th', 'td'], true)) {
                    $cells[] = trim($this->inline($cell));
                }
            }
            if ($cells !== []) {
                $rows[] = '| '.implode(' | ', $cells).' |';
            }
        }

        if ($rows === []) {
            return '';
        }

        // Header separator after the first row.
        $cols = substr_count($rows[0], '|') - 1;
        array_splice($rows, 1, 0, '|'.str_repeat(' --- |', max($cols, 1)));

        return implode("\n", $rows)."\n\n";
    }

    private function inline(DOMNode $node): string
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMText) {
                $out .= $child->textContent;
            } elseif ($child instanceof DOMElement) {
                $out .= match ($child->localName) {
                    'code' => '`'.trim($child->textContent).'`',
                    'strong', 'b' => '**'.$this->inline($child).'**',
                    'em', 'i' => '*'.$this->inline($child).'*',
                    'a' => '['.$this->inline($child).']('.$child->getAttribute('href').')',
                    'br' => "\n",
                    default => $this->inline($child),
                };
            }
        }

        return (string) preg_replace('/[ \t]+/', ' ', $out);
    }
}
