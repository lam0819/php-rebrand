<?php

declare(strict_types=1);

use App\Docs\Rendering\PhpSyntaxHighlighter;

beforeEach(function () {
    $this->highlight = fn (string $code): string => (new PhpSyntaxHighlighter)->highlight($code);
});

it('highlights the core PHP token types', function () {
    $html = ($this->highlight)('<?php $n = strlen("hi"); // comment'."\n".'if ($n) { echo true; }');

    expect($html)
        ->toContain('<span class="tk-php">&lt;?php')
        ->toContain('<span class="tk-var">$n</span>')
        ->toContain('<span class="tk-fn">strlen</span>')
        ->toContain('<span class="tk-str">&quot;hi&quot;</span>')
        ->toContain('<span class="tk-com">// comment</span>')
        ->toContain('<span class="tk-kw">if</span>')
        ->toContain('<span class="tk-kw">echo</span>')
        ->toContain('<span class="tk-kw">true</span>');
});

it('escapes code so output is safe to print raw', function () {
    $html = ($this->highlight)('<?php echo "<b>" . $x & $y;');

    expect($html)
        ->not->toContain('<b>')      // the string literal is escaped
        ->toContain('&amp;');        // the & operator is escaped
});

it('highlights a bare fragment without an open tag', function () {
    $html = ($this->highlight)('$total = array_sum($items);');

    expect($html)
        ->toContain('<span class="tk-var">$total</span>')
        ->toContain('<span class="tk-fn">array_sum</span>')
        ->not->toContain('&lt;?php'); // the injected open tag is dropped
});

it('leaves inline HTML around PHP unhighlighted and escaped', function () {
    $html = ($this->highlight)('<div><?php echo $x; ?></div>');

    expect($html)
        ->toContain('&lt;div&gt;')                     // surrounding HTML is escaped, not coloured
        ->toContain('<span class="tk-var">$x</span>'); // the PHP inside is highlighted
});
