@php
    use Illuminate\Support\Str;

    $typePill = [
        'refentry' => 'PHP function',
        'reference' => 'Reference',
        'chapter' => 'Guide',
        'article' => 'Guide',
        'appendix' => 'Appendix',
    ];

    $prettyDir = function (string $dir): string {
        $parts = array_values(array_filter(explode('/', $dir)));
        return implode(' / ', array_slice($parts, -2)) ?: 'Manual';
    };

    $editUrl = $doc['sourcePath']
        ? 'https://github.com/php/doc-en/blob/master/'.$doc['sourcePath']
        : null;

    $body = (string) ($doc['bodyHtml'] ?? '');

    // Build the "on this page" TOC from the rendered section headings (<h2 id>).
    $toc = [];
    if (preg_match_all('/<h2 id="([^"]+)">(.*?)<\/h2>/s', $body, $m, PREG_SET_ORDER)) {
        foreach ($m as $h) {
            $toc[] = ['id' => $h[1], 'label' => trim(strip_tags($h[2]))];
        }
    }
    if ($children !== []) { $toc[] = ['id' => 'in-this-section', 'label' => 'In this section']; }

    $metaDescription = Str::limit($doc['purpose'] ?: ($doc['title'].' — PHP manual.'), 160);

    // Built here in the @php block (not the template body) so the literal
    // "@context" JSON-LD key is not mistaken for Blade's @context directive.
    $jsonLd = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'TechArticle',
        'headline' => $doc['title'],
        'description' => $metaDescription,
        'url' => url()->current(),
        'isPartOf' => ['@type' => 'WebSite', 'name' => 'PHP Manual', 'url' => url('/docs')],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp

<x-layouts.app :title="$doc['title'].' — PHP Manual'" :description="$metaDescription">
  <x-slot:head>
    <script type="application/ld+json">{!! $jsonLd !!}</script>
  </x-slot:head>

  <div class="container-wide">
    <div class="docs-shell">
      {{-- sidebar: sibling pages --}}
      <aside class="docs-side">
        <nav>
          <div class="side-group">
            <p class="side-label">{{ $dir ? $prettyDir($dir) : 'Manual' }}</p>
            @forelse ($siblings as $s)
              @php($isCurrent = $s['slug'] === $doc['slug'])
              <a class="side-link {{ $isCurrent ? 'active' : '' }}" href="/manual/{{ $s['slug'] }}" wire:navigate>
                @if ($s['kind'] === 'refentry')<span class="mono">{{ $s['name'] }}</span>@else{{ $s['name'] }}@endif
              </a>
              @if ($isCurrent && count($toc) > 1)
                {{-- Current page's sections — the in-page nav, shown where the
                     right-hand "On this page" TOC is hidden (narrow viewports). --}}
                <div class="side-sub">
                  @foreach ($toc as $t)
                    <a href="#{{ $t['id'] }}">{{ $t['label'] }}</a>
                  @endforeach
                </div>
              @endif
            @empty
              <p class="meta" style="padding: 6px 10px">—</p>
            @endforelse
          </div>
          <div class="side-group">
            <a class="side-link side-back" href="/docs" wire:navigate>← All of the manual</a>
          </div>
        </nav>
      </aside>

      <div class="docs-main">
        <div class="fn-layout">
          <article class="doc">
            <div class="crumb" style="margin-bottom: 16px">
              <a href="/" wire:navigate>Home</a><span class="sep">/</span>
              <a href="/docs" wire:navigate>Manual</a><span class="sep">/</span>
              @if ($dir)<span>{{ $prettyDir($dir) }}</span><span class="sep">/</span>@endif
              <span class="accent">{{ $doc['title'] }}</span>
            </div>

            <div class="row-between" style="margin-bottom: 6px; flex-wrap: wrap; gap: 12px">
              <h1 class="fn-title">{{ $doc['title'] }}</h1>
              <div class="row" style="gap: 8px">
                <span class="pill pill-accent">{{ $typePill[$doc['type']] ?? $doc['type'] }}</span>
                @if ($doc['removed'])
                  <span class="pill pill-danger">Removed in {{ $doc['removed'] }}</span>
                @elseif ($doc['deprecated'])
                  <span class="pill pill-warn">Deprecated as of {{ $doc['deprecated'] }}</span>
                @endif
                @if ($editUrl)
                  <a class="btn btn-secondary btn-sm" href="{{ $editUrl }}" target="_blank" rel="noreferrer">Edit on GitHub ✎</a>
                @endif
              </div>
            </div>

            {{-- Version availability, sourced from php/doc-en's versions.xml --}}
            @if ($doc['version'])
              <p class="verinfo">({{ $doc['version'] }})</p>
            @endif

            @if ($doc['purpose'])
              <p class="lead doc-lead">{{ $doc['purpose'] }}</p>
            @endif

            {{-- Chapter contents: an at-a-glance summary of the sections on this
                 page (php.net splits these across sub-pages; we keep them on one
                 page and surface this overview up top instead). --}}
            @if ($doc['type'] !== 'refentry' && count($toc) > 1)
              <nav class="chapter-toc" aria-label="In this chapter">
                <p class="chapter-toc-label">In this chapter</p>
                <ol>
                  @foreach ($toc as $t)
                    <li><a href="#{{ $t['id'] }}">{{ $t['label'] }}</a></li>
                  @endforeach
                </ol>
              </nav>
            @endif

            {{-- The full DocBook body, rendered to HTML (safe: built from a fixed
                 allow-list of elements, every value escaped). --}}
            @if ($body !== '')
              <div class="doc-body">{!! $body !!}</div>
            @endif

            {{-- Child pages (index/chapter pages link to their sub-pages) --}}
            @if ($children !== [])
              <h2 id="in-this-section">In this section</h2>
              <div class="grid-3 keep-2">
                @foreach ($children as $c)
                  <a class="card card-link" href="/manual/{{ $c['slug'] }}" wire:navigate>
                    <h3 class="mono accent" style="font-size: 16px">{{ $c['name'] }}</h3>
                    @if ($c['desc'])<p class="meta" style="margin-top: 6px">{{ Str::limit($c['desc'], 90) }}</p>@endif
                  </a>
                @endforeach
              </div>
            @endif

            <p class="meta mt-xl">
              Source: <a class="accent" href="{{ $editUrl }}" target="_blank" rel="noreferrer">{{ $doc['sourcePath'] }}</a> · from the official PHP manual (php/doc-en)
            </p>
          </article>

          {{-- on this page --}}
          @if ($toc !== [])
            <aside class="toc" data-toc>
              <p class="side-label">On this page</p>
              @foreach ($toc as $t)
                <a href="#{{ $t['id'] }}">{{ $t['label'] }}</a>
              @endforeach
            </aside>
          @endif
        </div>
      </div>
    </div>
  </div>
  <div style="height: var(--gap-2xl)"></div>
</x-layouts.app>
