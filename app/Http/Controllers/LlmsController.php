<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Docs\Persistence\DocPage;
use App\Docs\Rendering\HtmlToMarkdown;
use App\Docs\Support\DocPagePresenter;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * AI-agent friendly endpoints: an /llms.txt index (per llmstxt.org) and a
 * Markdown rendering of any manual page at /manual/{slug}.md.
 */
final class LlmsController extends Controller
{
    public function __construct(
        private readonly DocPagePresenter $presenter,
        private readonly HtmlToMarkdown $markdown,
    ) {}

    /**
     * The llms.txt index: a concise, link-rich map of the site for LLMs.
     */
    public function index(): Response
    {
        $language = [
            'language-basic-syntax' => 'Basic syntax',
            'language-types' => 'Types',
            'language-variables' => 'Variables',
            'language-operators' => 'Operators',
            'language-control-structures' => 'Control structures',
            'language-functions' => 'Functions',
            'language-oop5' => 'Classes and objects',
            'language-enumerations' => 'Enumerations',
            'language-attributes' => 'Attributes',
            'language-namespaces' => 'Namespaces',
            'language-exceptions' => 'Exceptions',
            'language-fibers' => 'Fibers',
        ];

        $categories = ['array', 'string', 'preg', 'json', 'date', 'math', 'pdo', 'mbstring', 'curl', 'hash'];

        $lines = [];
        $lines[] = '# PHP Manual';
        $lines[] = '';
        $lines[] = '> A modern, fast rebuild of the official PHP documentation, generated directly from the '
            .'php/doc-en source. Over 11,000 full-text searchable manual pages with runnable examples.';
        $lines[] = '';
        $lines[] = 'Every manual page is available as Markdown by appending `.md` to its URL '
            .'(e.g. '.url('/manual/language-fibers.md').'). A read-only JSON API is also available under /api/docs.';
        $lines[] = '';

        $lines[] = '## Language reference';
        foreach ($language as $slug => $label) {
            $lines[] = '- ['.$label.']('.url('/manual/'.$slug).'): '.$label.' — '.url('/manual/'.$slug.'.md');
        }
        $lines[] = '';

        $lines[] = '## Function reference';
        foreach ($categories as $cat) {
            $lines[] = '- ['.ucfirst($cat).' functions]('.url('/docs?q='.$cat).'): browse and search '.$cat.' functions';
        }
        $lines[] = '';

        $lines[] = '## Tools';
        $lines[] = '- [Search the manual]('.url('/docs').'): full-text search across every page';
        $lines[] = '- [JSON API]('.url('/api/docs').'): programmatic access (`/api/docs`, `/api/docs/search?q=`, `/api/docs/{slug}`)';
        $lines[] = '- [Sitemap]('.url('/sitemap.xml').'): every page URL';
        $lines[] = '- [Downloads]('.url('/downloads').'): PHP releases';
        $lines[] = '';

        return $this->text(implode("\n", $lines));
    }

    /**
     * A single manual page rendered as Markdown.
     */
    public function page(string $slug): Response
    {
        $page = DocPage::query()->where('slug', $slug)->first();

        if ($page === null) {
            throw new NotFoundHttpException("No manual page for [{$slug}].");
        }

        $doc = $this->presenter->present($page);

        $md = '# '.$doc['title']."\n\n";
        if ($doc['purpose']) {
            $md .= '> '.$doc['purpose']."\n\n";
        }
        $md .= $this->markdown->convert((string) $doc['bodyHtml']);
        $md .= "\n\n---\n\nSource: https://github.com/php/doc-en/blob/master/".$doc['sourcePath']
            .' · canonical: '.url('/manual/'.$slug)."\n";

        return $this->text($md);
    }

    private function text(string $body): Response
    {
        return response($body, 200)->header('Content-Type', 'text/markdown; charset=utf-8');
    }
}
