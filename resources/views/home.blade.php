@php($leadVersion = $releases[0]['version'] ?? '8.5.7')
<x-layouts.app>
  {{-- ── hero (split with ElePHPant) ───────────────────────────── --}}
  <section class="section hero-glow" style="padding-block: clamp(44px,6vw,84px) clamp(36px,5vw,60px);">
    <div class="container">
      <div class="hero-split-2">
        <div class="anim-in">
          <span class="pill pill-accent pill-dot" style="margin-bottom:20px;">PHP 8.5 is here</span>
          <h1 style="max-width:15ch;">The pragmatic language the web runs on.</h1>
          <p class="lead mt-md" style="max-width:46ch;">Fast, flexible and grounded. After 30 years PHP has never been more modern, typed, and pleasant to write.</p>
          <div class="row-wrap mt-lg">
            <a class="btn btn-primary btn-lg" href="/downloads" wire:navigate>Download {{ $leadVersion }}</a>
            <a class="btn btn-secondary btn-lg btn-arrow" href="/news" wire:navigate>What's new in 8.5</a>
          </div>
          <div class="mt-lg" style="max-width:520px;">
            <form class="searchbar lg" method="GET" action="/docs" role="search">
              <span class="s-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/></svg></span>
              <input data-search name="q" type="search" placeholder="Search the manual — array_map, enums, PDO…" aria-label="Search the PHP manual" autocomplete="off" />
              <kbd>/</kbd>
            </form>
            <p class="meta mt-sm">Try <a class="accent" href="/manual/array_map" wire:navigate>array_map</a>, <a class="accent" href="/manual/language-enumerations" wire:navigate>Enumerations</a>, or <a class="accent" href="/manual/language-control-structures-match" wire:navigate>Match expression</a></p>
          </div>
        </div>

        <div class="hero-mascot anim-in d2">
          <div class="mascot-panel">
            <svg viewBox="0 0 400 360" role="img" aria-label="The modern ElePHPant — PHP's mascot" style="color:var(--accent);">
              <ellipse cx="208" cy="322" rx="138" ry="18" fill="currentColor" opacity="0.10"/>
              <rect x="150" y="248" width="42" height="74" rx="21" fill="currentColor"/>
              <rect x="234" y="248" width="42" height="74" rx="21" fill="currentColor"/>
              <rect x="206" y="252" width="40" height="70" rx="20" fill="var(--fg)" opacity="0.10"/>
              <rect x="120" y="150" width="214" height="152" rx="76" fill="currentColor"/>
              <ellipse cx="232" cy="244" rx="74" ry="46" fill="var(--surface)" opacity="0.12"/>
              <circle cx="150" cy="180" r="92" fill="currentColor"/>
              <circle cx="168" cy="150" r="58" fill="currentColor"/>
              <circle cx="174" cy="154" r="38" fill="var(--fg)" opacity="0.12"/>
              <path d="M92 176 q-36 22 -30 72 q4 38 38 44 q26 4 26 -19 q0 -15 -17 -15 q-13 0 -11 13" fill="none" stroke="currentColor" stroke-width="34" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M106 214 q-15 16 -7 35" fill="none" stroke="var(--surface)" stroke-width="8" stroke-linecap="round" opacity="0.85"/>
              <circle cx="120" cy="168" r="11" fill="var(--surface)"/>
              <circle cx="123" cy="170" r="5.5" fill="var(--fg)"/>
            </svg>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- ── current releases ──────────────────────────────────────── --}}
  <section class="section" style="padding-block:clamp(36px,5vw,64px);">
    <div class="container">
      <div class="row-between" style="margin-bottom:24px;">
        <div>
          <p class="eyebrow" style="margin-bottom:6px;">Supported releases</p>
          <h2 style="font-size:clamp(24px,3vw,32px);">Stay current, stay secure</h2>
        </div>
        <a class="btn btn-ghost btn-arrow hide-sm" href="/downloads" wire:navigate>All downloads</a>
      </div>
      <div class="grid-4 keep-2">
        @foreach ($releases as $rel)
          <div class="card">
            <div class="row-between" style="margin-bottom:12px;"><span class="num h3">{{ $rel['version'] }}</span><span class="pill {{ $rel['pill'] }}">{{ $rel['tag'] }}</span></div>
            <p class="card-meta">{{ $rel['note'] }}</p>
            <p class="meta mt-md"><a class="accent" href="{{ route('changelog.show', $rel['version']) }}" wire:navigate>Changelog</a> · <a class="accent" href="/downloads" wire:navigate>Download</a></p>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ── code showcase ─────────────────────────────────────────── --}}
  <section class="section" style="border-top:1px solid var(--border);">
    <div class="container">
      <div class="grid-2" style="align-items:center;gap:var(--gap-2xl);">
        <div>
          <p class="eyebrow">Modern by default</p>
          <h2>Typed, expressive, and readable out of the box.</h2>
          <p class="lead mt-md">Readonly properties, named arguments, enums, and property hooks. The PHP you write today looks nothing like 2004 — and runs dramatically faster.</p>
          <div class="row-wrap mt-lg">
            <a class="btn btn-primary" href="/docs" wire:navigate>Read the manual</a>
            <a class="btn btn-ghost btn-arrow" href="/docs?type=refentry" wire:navigate>Browse functions</a>
          </div>
        </div>
        <div class="code">
          <div class="code-head">
            <span class="fname">Money.php</span>
            <button class="copy-btn" data-copy><span class="copy-label">Copy</span></button>
          </div>
<pre><code><span class="tk-php">&lt;?php</span>

<span class="tk-kw">final class</span> <span class="tk-fn">Money</span>
{
    <span class="tk-kw">public function</span> <span class="tk-fn">__construct</span>(
        <span class="tk-kw">public readonly</span> <span class="tk-kw">int</span> <span class="tk-var">$amount</span>,
        <span class="tk-kw">public readonly</span> <span class="tk-kw">string</span> <span class="tk-var">$currency</span> = <span class="tk-str">'USD'</span>,
    ) {}

    <span class="tk-com">// property hook — computed, read-only</span>
    <span class="tk-kw">public string</span> <span class="tk-var">$display</span> {
        <span class="tk-kw">get</span> =&gt; <span class="tk-fn">number_format</span>(<span class="tk-var">$this</span>-&gt;amount / <span class="tk-num">100</span>, <span class="tk-num">2</span>)
              . <span class="tk-str">" {$this->currency}"</span>;
    }
}

<span class="tk-var">$price</span> = <span class="tk-kw">new</span> <span class="tk-fn">Money</span>(amount: <span class="tk-num">4_999</span>);
<span class="tk-kw">echo</span> <span class="tk-var">$price</span>-&gt;display;   <span class="tk-com">// 49.99 USD</span></code></pre>
        </div>
      </div>
    </div>
  </section>

  {{-- ── why php · organized for discovery ─────────────────────── --}}
  <section class="section" style="border-top:1px solid var(--border);">
    <div class="container">
      <div class="maxw-48" style="margin-bottom:48px;">
        <p class="eyebrow">Built to be found</p>
        <h2>Documentation you can actually navigate.</h2>
        <p class="lead mt-md">The manual is PHP's superpower. We rebuilt search and organization around the way developers really look things up.</p>
      </div>
      <div class="grid-3">
        <div class="card">
          <div class="feature-mark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/></svg></div>
          <h3>Instant search</h3>
          <p class="card-meta">Type part of a function, class, or concept and jump straight there. Press <code class="inline">/</code> anywhere to focus search.</p>
        </div>
        <div class="card">
          <div class="feature-mark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 6h16M4 12h10M4 18h16"/></svg></div>
          <h3>Reference, organized</h3>
          <p class="card-meta">11,000+ pages grouped by extension, with version availability and parameters surfaced before you scroll.</p>
        </div>
        <div class="card">
          <div class="feature-mark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M8 6 3 12l5 6M16 6l5 6-5 6"/></svg></div>
          <h3>Real, copyable examples</h3>
          <p class="card-meta">Every reference page ships runnable snippets you can copy in one click — typed, current, and idiomatic.</p>
        </div>
      </div>
    </div>
  </section>

  {{-- ── news feed ─────────────────────────────────────────────── --}}
  <section class="section" style="border-top:1px solid var(--border);">
    <div class="container">
      <div class="row-between" style="margin-bottom:8px;">
        <h2>Latest news</h2>
        <a class="btn btn-ghost btn-arrow" href="/news" wire:navigate>All announcements</a>
      </div>
      @foreach ($news as $item)
        @php($tag = $presenter->newsTag($item))
        <a class="log-row card-link" href="{{ route('news.show', $item->entry_id) }}" wire:navigate>
          <span class="meta">{{ $item->published_at->format('d M Y') }}</span>
          <div>
            <h3>{{ $item->title }}</h3>
            <p>{{ \Illuminate\Support\Str::of($item->body_html)->stripTags()->squish()->limit(150) }}</p>
          </div>
          <span class="pull pill {{ $tag['pill'] }}">{{ $tag['tag'] }}</span>
        </a>
      @endforeach
    </div>
  </section>

  {{-- ── foundation strip ──────────────────────────────────────── --}}
  <section class="section" style="border-top:1px solid var(--border);">
    <div class="container">
      <div class="card hero-glow" style="padding:clamp(28px,5vw,52px);text-align:center;">
        <p class="eyebrow" style="justify-content:center;">The PHP Foundation</p>
        <h2 class="maxw-32 mx-auto">PHP is built in the open, funded by people who depend on it.</h2>
        <p class="lead mx-auto mt-md" style="max-width:48ch;">The Foundation employs core developers to keep the language healthy for the next decade. Join the companies and individuals supporting the work.</p>
        <div class="row-wrap mt-lg" style="justify-content:center;">
          <a class="btn btn-primary" href="/get-involved" wire:navigate>Support the Foundation</a>
          <a class="btn btn-ghost btn-arrow" href="/get-involved" wire:navigate>Ways to contribute</a>
        </div>
      </div>
    </div>
  </section>
</x-layouts.app>
