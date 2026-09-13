<?php

declare(strict_types=1);

namespace App\Docs\Rendering;

use DOMElement;
use DOMNode;
use DOMText;

/**
 * Renders a DocBook document body to semantic HTML that matches the site's
 * design components (.note, .code, .ds-table, .sig, .var-list, inline <code>).
 *
 * The whole page is walked recursively so nothing is dropped — prose, examples,
 * notes, parameter lists, tables and nested sections all render, not just the
 * first paragraph. Two DocBook realities are handled explicitly:
 *
 *  - Section titles are sometimes entity text (e.g. &reftitle.description; in a
 *    <refsect1>) rather than a <title> element.
 *  - Block elements (<variablelist>, <example>, …) are frequently nested inside
 *    a <para> (mixed content), so inline runs and blocks are interleaved.
 *
 * Output is built from a fixed allow-list of elements (every value escaped), so
 * it is safe to print with {!! !!}.
 */
final class DocBookHtmlRenderer
{
    public function __construct(
        private readonly PhpSyntaxHighlighter $highlighter = new PhpSyntaxHighlighter,
    ) {}

    /** Structural elements carrying a heading and a body of child blocks. */
    private const CONTAINERS = [
        'chapter', 'section', 'sect1', 'sect2', 'sect3', 'sect4', 'simplesect',
        'refsect1', 'refsect2', 'refsect3', 'article', 'appendix', 'part',
        'preface', 'book', 'reference', 'partintro',
    ];

    /** Admonitions → the .note component (warn variant for some). */
    private const ADMONITIONS = ['note', 'warning', 'caution', 'important', 'tip'];

    /** Everything that must break the inline flow and render as its own block. */
    private const BLOCK = [
        ...self::CONTAINERS,
        ...self::ADMONITIONS,
        'para', 'simpara', 'programlisting', 'screen', 'itemizedlist',
        'orderedlist', 'simplelist', 'variablelist', 'example', 'informalexample',
        'table', 'informaltable', 'blockquote', 'methodsynopsis',
        'constructorsynopsis', 'destructorsynopsis', 'classsynopsisinfo',
        'classsynopsis', 'title', 'titleabbrev', 'refnamediv', 'indexterm',
    ];

    /** Elements rendered as a single inline <code> chip. */
    private const CODE_INLINE = [
        'methodname', 'function', 'classname', 'interfacename', 'varname',
        'parameter', 'constant', 'literal', 'code', 'type', 'property',
        'exceptionname', 'envar', 'filename', 'command', 'option', 'methodparam',
        'member', 'package', 'directory', 'computeroutput', 'userinput', 'tag',
        'enumname', 'modifier', 'replaceable', 'sgmltag',
    ];

    public function render(DOMElement $root): string
    {
        return trim($this->content($root, 2));
    }

    /**
     * Walk a node's children, rendering block elements as blocks and wrapping
     * runs of inline content (text + inline elements) in <p>. $skip, if given,
     * is omitted (used to drop a heading source node).
     */
    private function content(DOMNode $node, int $level, ?DOMNode $skip = null): string
    {
        $html = '';
        $buffer = '';

        foreach ($node->childNodes as $child) {
            if ($skip !== null && $child->isSameNode($skip)) {
                continue;
            }

            if ($child instanceof DOMText) {
                $buffer .= $this->escape($child->textContent);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            if ($this->isBlock($child)) {
                $html .= $this->flushInline($buffer);
                $buffer = '';
                $html .= $this->block($child, $level);
            } else {
                $buffer .= $this->inlineElement($child);
            }
        }

        return $html.$this->flushInline($buffer);
    }

    private function flushInline(string $buffer): string
    {
        $text = $this->collapse($buffer);

        return $text === '' ? '' : '<p>'.$text.'</p>';
    }

    private function isBlock(DOMElement $node): bool
    {
        return in_array($node->localName, self::BLOCK, true);
    }

    private function block(DOMElement $node, int $level): string
    {
        $name = $node->localName;

        if (in_array($name, self::CONTAINERS, true)) {
            return $this->container($node, $level);
        }

        if (in_array($name, self::ADMONITIONS, true)) {
            return $this->admonition($node, $level);
        }

        return match ($name) {
            'para', 'simpara' => $this->content($node, $level),
            'programlisting', 'screen', 'classsynopsisinfo' => $this->code($node),
            'example', 'informalexample' => $this->example($node, $level),
            'itemizedlist', 'simplelist' => $this->list($node, 'ul', $level),
            'orderedlist' => $this->list($node, 'ol', $level),
            'variablelist' => $this->variableList($node, $level),
            'table', 'informaltable' => $this->table($node),
            'blockquote' => '<blockquote>'.$this->content($node, $level).'</blockquote>',
            'methodsynopsis', 'constructorsynopsis', 'destructorsynopsis' => $this->synopsis($node),
            'classsynopsis' => $this->classSynopsis($node),
            default => '', // title / titleabbrev / refnamediv / indexterm — rendered elsewhere or dropped
        };
    }

    private function container(DOMElement $node, int $level): string
    {
        [$heading, $skip] = $this->heading($node);

        $html = '';
        if ($heading !== '') {
            $tag = 'h'.min($level, 4);
            $idAttr = $level <= 2 ? ' id="'.$this->escapeAttr($this->slugId($heading)).'"' : '';
            $html .= '<'.$tag.$idAttr.'>'.$heading.'</'.$tag.'>';
        }

        return $html.$this->content($node, $level + 1, $skip);
    }

    /**
     * A container's heading: the <title> element if present, otherwise the
     * leading text node (DocBook refsect titles are entity text like
     * &reftitle.description;). Returns [html, nodeToSkip].
     *
     * @return array{0: string, 1: ?DOMNode}
     */
    private function heading(DOMElement $node): array
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === 'title') {
                return [$this->inline($child), $child];
            }
            if ($child instanceof DOMElement) {
                break; // a non-title element comes first → no entity-title
            }
            if ($child instanceof DOMText && trim($child->textContent) !== '') {
                return [$this->escape($this->collapse($child->textContent)), $child];
            }
        }

        return ['', null];
    }

    private function admonition(DOMElement $node, int $level): string
    {
        $name = (string) $node->localName;
        $warn = in_array($name, ['warning', 'caution', 'important'], true);

        return '<div class="note'.($warn ? ' warn' : '').'">'
            .'<div class="note-label">'.ucfirst($name).'</div>'
            .$this->content($node, $level)
            .'</div>';
    }

    private function code(DOMElement $node): string
    {
        $code = trim($node->textContent, "\r\n");
        $isOutput = $node->localName === 'screen';
        $role = $node->getAttribute('role');
        $lang = $isOutput ? 'output' : ($role !== '' ? $role : 'php');

        // PHP samples are highlighted with the real tokenizer; everything else
        // (output, ini, xml, …) is shown verbatim.
        $body = $lang === 'php' ? $this->highlighter->highlight($code) : $this->escape($code);

        return '<div class="code">'
            .'<div class="code-head"><span class="fname">'.$this->escape($lang).'</span>'
            .($isOutput ? '' : '<button type="button" class="copy-btn" data-copy>Copy</button>')
            .'</div>'
            .'<pre><code>'.$body.'</code></pre>'
            .'</div>';
    }

    private function example(DOMElement $node, int $level): string
    {
        $html = '';
        $title = $this->firstChildElement($node, 'title');

        if ($title !== null) {
            $html .= '<p class="example-title"><strong>'.$this->inline($title).'</strong></p>';
        }

        return $html.$this->content($node, $level, $title);
    }

    private function list(DOMElement $node, string $tag, int $level): string
    {
        $items = '';
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && in_array($child->localName, ['listitem', 'member'], true)) {
                $items .= '<li>'.$this->unwrap($this->content($child, $level)).'</li>';
            }
        }

        return $items === '' ? '' : '<'.$tag.'>'.$items.'</'.$tag.'>';
    }

    private function variableList(DOMElement $node, int $level): string
    {
        $rows = '';
        foreach ($node->childNodes as $entry) {
            if (! $entry instanceof DOMElement || $entry->localName !== 'varlistentry') {
                continue;
            }
            foreach ($entry->childNodes as $child) {
                if ($child instanceof DOMElement && $child->localName === 'term') {
                    $rows .= '<dt>'.$this->inline($child).'</dt>';
                }
            }
            $listitem = $this->firstChildElement($entry, 'listitem');
            $rows .= '<dd>'.($listitem !== null ? $this->content($listitem, $level) : '').'</dd>';
        }

        return $rows === '' ? '' : '<dl class="var-list">'.$rows.'</dl>';
    }

    private function table(DOMElement $node): string
    {
        $head = '';
        $body = '';

        foreach ($node->getElementsByTagName('row') as $row) {
            $isHead = $this->hasAncestor($row, 'thead');
            $cells = '';
            foreach ($row->childNodes as $cell) {
                if ($cell instanceof DOMElement && $cell->localName === 'entry') {
                    $tag = $isHead ? 'th' : 'td';
                    $cells .= '<'.$tag.'>'.$this->inline($cell).'</'.$tag.'>';
                }
            }
            $rowHtml = '<tr>'.$cells.'</tr>';
            if ($isHead) {
                $head .= $rowHtml;
            } else {
                $body .= $rowHtml;
            }
        }

        if ($head === '' && $body === '') {
            return '';
        }

        return '<table class="ds-table">'
            .($head !== '' ? '<thead>'.$head.'</thead>' : '')
            .'<tbody>'.$body.'</tbody></table>';
    }

    /**
     * Render a method/function synopsis as a modern PHP signature block, e.g.
     * <code>strlen(string $string): int</code> — return type as a suffix,
     * union/intersection types joined with <code>|</code>/<code>&</code>,
     * variadic params as <code>...$name</code>, and defaults from
     * <initializer>.
     */
    private function synopsis(DOMElement $node): string
    {
        $returnType = $this->typeText($this->firstChildElement($node, 'type'));
        $method = $this->collapse($this->childText($node, 'methodname'));

        $params = [];
        foreach ($node->childNodes as $child) {
            if (! $child instanceof DOMElement || $child->localName !== 'methodparam') {
                continue;
            }
            $type = $this->typeText($this->firstChildElement($child, 'type')) ?: 'mixed';
            $name = $this->collapse($this->childText($child, 'parameter'));
            $variadic = $child->getAttribute('rep') === 'repeat' ? '...' : '';
            $piece = trim($type.' '.$variadic.'$'.$name);

            $initializer = $this->firstChildElement($child, 'initializer');
            if ($initializer !== null) {
                $piece .= ' = '.$this->collapse($initializer->textContent);
            } elseif ($child->getAttribute('choice') === 'opt') {
                $piece = '['.$piece.']';
            }
            $params[] = $piece;
        }

        $signature = $method.'('.implode(', ', $params).')';
        if ($returnType !== '') {
            $signature .= ': '.$returnType;
        }

        return '<div class="sig">'.$this->escape($signature).'</div>';
    }

    /**
     * The rendered text of a <type>, resolving DocBook union/intersection types
     * (<type class="union"><type>int</type><type>false</type></type>) to
     * <code>int|false</code> rather than the run-together "intfalse".
     */
    private function typeText(?DOMElement $type): string
    {
        if ($type === null) {
            return '';
        }

        $class = $type->getAttribute('class');
        if ($class === 'union' || $class === 'intersection') {
            $separator = $class === 'intersection' ? '&' : '|';
            $parts = [];
            foreach ($type->childNodes as $child) {
                if ($child instanceof DOMElement && $child->localName === 'type') {
                    $parts[] = $this->collapse($child->textContent);
                }
            }
            if ($parts !== []) {
                return implode($separator, $parts);
            }
        }

        return $this->collapse($type->textContent);
    }

    /**
     * Render the declaration line of a <classsynopsis> (class name, extends,
     * implements). The member lists inside rely on shared doc-base entities we
     * do not resolve, so only the declaration is emitted — never the raw entity
     * tokens.
     */
    private function classSynopsis(DOMElement $node): string
    {
        $segments = [];
        foreach ($node->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }
            if ($child->localName === 'ooclass') {
                $modifier = $this->collapse($this->childText($child, 'modifier'));
                $class = $this->collapse($this->childText($child, 'classname'));
                if ($class === '') {
                    continue;
                }
                $segments[] = $modifier !== '' ? $modifier.' '.$class : 'class '.$class;
            } elseif ($child->localName === 'oointerface') {
                $modifier = $this->collapse($this->childText($child, 'modifier')) ?: 'implements';
                $interface = $this->collapse($this->childText($child, 'interfacename'));
                if ($interface !== '') {
                    $segments[] = $modifier.' '.$interface;
                }
            }
        }

        return $segments === [] ? '' : '<div class="sig">'.$this->escape(implode(' ', $segments)).' { }</div>';
    }

    private function inline(DOMNode $node): string
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMText) {
                $out .= $this->escape($child->textContent);
            } elseif ($child instanceof DOMElement) {
                $out .= $this->inlineElement($child);
            }
        }

        return $this->collapse($out);
    }

    private function inlineElement(DOMElement $node): string
    {
        $name = $node->localName;

        // Union/intersection types render with their separator (int|false), not
        // the run-together text content.
        if ($name === 'type') {
            return '<code class="inline">'.$this->escape($this->typeText($node)).'</code>';
        }

        // Function references link to the page (as php.net does); the short
        // /manual/{name} URL resolves/redirects to the real slug.
        if ($name === 'function') {
            return $this->functionLink($node);
        }

        if (in_array($name, self::CODE_INLINE, true)) {
            return '<code class="inline">'.$this->escape($this->collapse($node->textContent)).'</code>';
        }

        return match ($name) {
            'emphasis' => in_array($node->getAttribute('role'), ['bold', 'strong'], true)
                ? '<strong>'.$this->inline($node).'</strong>'
                : '<em>'.$this->inline($node).'</em>',
            'link', 'xref' => $this->internalLink($node),
            'ulink' => $this->externalLink($node),
            default => $this->inline($node),
        };
    }

    /**
     * A reference to a PHP function, rendered as a link to its manual page,
     * e.g. <a href="/manual/array_map"><code>array_map()</code></a>. The short
     * URL is resolved to the canonical slug by the manual controller.
     */
    private function functionLink(DOMElement $node): string
    {
        $name = $this->collapse($node->textContent);
        if ($name === '') {
            return '';
        }

        $label = '<code class="inline">'.$this->escape($name).'()</code>';

        return '<a href="/manual/'.$this->escapeAttr($name).'">'.$label.'</a>';
    }

    private function internalLink(DOMElement $node): string
    {
        $text = $this->inline($node);
        $target = $node->getAttribute('linkend');

        if ($target === '') {
            return $text;
        }

        $slug = str_replace('.', '-', $target);

        return '<a href="/manual/'.$this->escapeAttr($slug).'">'.($text !== '' ? $text : $this->escape($target)).'</a>';
    }

    private function externalLink(DOMElement $node): string
    {
        $url = $node->getAttribute('url');
        $text = $this->inline($node);
        $text = $text !== '' ? $text : $this->escape($url);

        return $url === '' ? $text : '<a href="'.$this->escapeAttr($url).'" target="_blank" rel="noreferrer">'.$text.'</a>';
    }

    private function unwrap(string $html): string
    {
        if (preg_match('#^<p>(.*)</p>$#s', trim($html), $m) === 1 && ! str_contains($m[1], '<p>')) {
            return $m[1];
        }

        return $html;
    }

    private function firstChildElement(DOMElement $node, string $localName): ?DOMElement
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === $localName) {
                return $child;
            }
        }

        return null;
    }

    private function childText(DOMElement $node, string $localName): string
    {
        $child = $this->firstChildElement($node, $localName);

        return $child === null ? '' : $child->textContent;
    }

    private function hasAncestor(DOMNode $node, string $localName): bool
    {
        for ($n = $node->parentNode; $n !== null; $n = $n->parentNode) {
            if ($n instanceof DOMElement && $n->localName === $localName) {
                return true;
            }
        }

        return false;
    }

    private function slugId(string $heading): string
    {
        $slug = strtolower(trim(strip_tags($heading)));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';

        return trim($slug, '-') ?: 'section';
    }

    private function collapse(string $text): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $text));
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function escapeAttr(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
