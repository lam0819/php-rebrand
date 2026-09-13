@php
    $sidebar = [
        ['title' => 'Getting started', 'links' => [
            ['label' => 'Installation', 'href' => '/manual/install-unix-index'],
            ['label' => 'Your first script', 'href' => '/manual/chapters-tutorial'],
            ['label' => 'Downloads', 'href' => '/downloads'],
        ]],
        ['title' => 'Language reference', 'links' => [
            ['label' => 'Basic syntax', 'href' => '/manual/language-basic-syntax'],
            ['label' => 'Types', 'href' => '/manual/language-types'],
            ['label' => 'Variables', 'href' => '/manual/language-variables'],
            ['label' => 'Operators', 'href' => '/manual/language-operators'],
            ['label' => 'Control structures', 'href' => '/manual/language-control-structures'],
            ['label' => 'Functions', 'href' => '/manual/language-functions'],
            ['label' => 'Classes & objects', 'href' => '/manual/language-oop5'],
            ['label' => 'Enumerations', 'href' => '/manual/language-enumerations'],
            ['label' => 'Attributes', 'href' => '/manual/language-attributes'],
            ['label' => 'Namespaces', 'href' => '/manual/language-namespaces'],
        ]],
        ['title' => 'Function reference', 'mono' => true, 'links' => [
            ['label' => 'Arrays', 'href' => '/docs?q=array'],
            ['label' => 'Strings', 'href' => '/docs?q=string'],
            ['label' => 'PCRE', 'href' => '/docs?q=preg'],
            ['label' => 'JSON', 'href' => '/docs?q=json'],
            ['label' => 'Date/Time', 'href' => '/docs?q=date'],
            ['label' => 'PDO', 'href' => '/docs?q=pdo'],
        ]],
    ];
@endphp

<x-layouts.app
    title="Documentation — PHP Manual"
    description="Browse and search the full PHP manual: language reference, 11,000+ function pages, guides and migration notes.">

  {{-- ── docs hero ─────────────────────────────────────────────── --}}
  <section class="section hero-glow" style="padding-block:clamp(40px,5vw,64px) clamp(20px,3vw,32px);">
    <div class="container center anim-in">
      <p class="eyebrow" style="justify-content:center;">PHP Manual · 8.5</p>
      <h1 style="font-size:clamp(34px,4.5vw,56px);max-width:18ch;margin-inline:auto;">Find any function, class, or concept in seconds.</h1>
    </div>
  </section>

  {{-- ── manual: sidebar + organized reference ─────────────────── --}}
  <div class="container-wide">
    <div class="docs-shell">
      <aside class="docs-side">
        <nav>
          @foreach ($sidebar as $group)
            <div class="side-group">
              <p class="side-label">{{ $group['title'] }}</p>
              @foreach ($group['links'] as $link)
                <a class="side-link" href="{{ $link['href'] }}" wire:navigate>
                  @if ($group['mono'] ?? false)<span class="mono">{{ $link['label'] }}</span>@else{{ $link['label'] }}@endif
                </a>
              @endforeach
            </div>
          @endforeach
        </nav>
      </aside>

      <div class="docs-main">
        <div class="crumb" style="margin-bottom:18px;">
          <a href="/" wire:navigate>Home</a><span class="sep">/</span><span>Manual</span><span class="sep">/</span><span class="accent">Reference</span>
        </div>
        <h2 style="margin-bottom:8px;">Browse the reference</h2>
        <p class="lead" style="margin-bottom:22px;">Everything in the manual, grouped the way you look it up. Filter by name to narrow instantly.</p>

        <livewire:docs />
      </div>
    </div>
  </div>
  <div style="height:var(--gap-2xl);"></div>
</x-layouts.app>
