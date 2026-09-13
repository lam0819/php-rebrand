<?php

declare(strict_types=1);

namespace App\Docs\Rendering;

/**
 * Highlights PHP code samples to HTML using PHP's own tokenizer, so the manual's
 * examples render with the same accuracy as the engine sees them. Output is a
 * run of <span class="tk-…"> wrappers around HTML-escaped text — safe to print.
 *
 * Done at import time and baked into body_html: no client-side highlighter, no
 * runtime cost.
 */
final class PhpSyntaxHighlighter
{
    /** Token ids coloured as language keywords. */
    private const KEYWORDS = [
        T_ECHO, T_PRINT, T_IF, T_ELSE, T_ELSEIF, T_ENDIF, T_WHILE, T_ENDWHILE,
        T_DO, T_FOR, T_ENDFOR, T_FOREACH, T_ENDFOREACH, T_AS, T_SWITCH,
        T_ENDSWITCH, T_CASE, T_DEFAULT, T_BREAK, T_CONTINUE, T_RETURN,
        T_FUNCTION, T_FN, T_CONST, T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM,
        T_EXTENDS, T_IMPLEMENTS, T_NEW, T_CLONE, T_PUBLIC, T_PRIVATE,
        T_PROTECTED, T_STATIC, T_ABSTRACT, T_FINAL, T_READONLY, T_VAR,
        T_GLOBAL, T_NAMESPACE, T_USE, T_INSTEADOF, T_TRY, T_CATCH, T_FINALLY,
        T_THROW, T_MATCH, T_YIELD, T_YIELD_FROM, T_INSTANCEOF, T_GOTO,
        T_DECLARE, T_ENDDECLARE, T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE,
        T_INCLUDE_ONCE, T_LIST, T_ARRAY, T_ISSET, T_UNSET, T_EMPTY, T_EXIT,
        T_EVAL, T_LOGICAL_AND, T_LOGICAL_OR, T_LOGICAL_XOR, T_CALLABLE,
    ];

    /** Bare identifiers coloured as keywords (the tokenizer reports these as T_STRING). */
    private const KEYWORD_WORDS = ['true', 'false', 'null', 'parent', 'self'];

    public function highlight(string $code): string
    {
        // token_get_all only tokenises PHP after an open tag; for a bare fragment
        // (role="php" without "<?php") inject one and drop it from the output.
        $synthetic = preg_match('/<\?/', $code) !== 1;
        $source = $synthetic ? "<?php\n".$code : $code;

        $tokens = @token_get_all($source);
        $out = '';
        $skipOpen = $synthetic;
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if (! is_array($token)) {
                $out .= $this->escape($token); // single-char punctuation/operators

                continue;
            }

            [$id, $text] = $token;

            if ($skipOpen && $id === T_OPEN_TAG) {
                $skipOpen = false; // drop the injected "<?php\n"

                continue;
            }

            $out .= $this->span($this->classFor($id, $text, $this->isCall($tokens, $i)), $text);
        }

        return $out;
    }

    /**
     * Whether the identifier at $i is a function/method call — the next
     * significant token is an opening parenthesis.
     *
     * @param  list<array{int, string, int}|string>  $tokens
     */
    private function isCall(array $tokens, int $i): bool
    {
        for ($j = $i + 1, $n = count($tokens); $j < $n; $j++) {
            $next = $tokens[$j];
            if (is_array($next) && $next[0] === T_WHITESPACE) {
                continue;
            }

            return $next === '(';
        }

        return false;
    }

    private function classFor(int $id, string $text, bool $isCall): string
    {
        if (in_array($id, self::KEYWORDS, true)) {
            return 'tk-kw';
        }

        return match ($id) {
            T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_CLOSE_TAG => 'tk-php',
            T_COMMENT, T_DOC_COMMENT => 'tk-com',
            T_VARIABLE => 'tk-var',
            T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE, T_STRING_VARNAME => 'tk-str',
            T_LNUMBER, T_DNUMBER => 'tk-num',
            T_STRING => match (true) {
                in_array(strtolower($text), self::KEYWORD_WORDS, true) => 'tk-kw',
                $isCall => 'tk-fn',
                default => '',
            },
            default => '',
        };
    }

    private function span(string $class, string $text): string
    {
        $escaped = $this->escape($text);

        return $class === '' ? $escaped : '<span class="'.$class.'">'.$escaped.'</span>';
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
