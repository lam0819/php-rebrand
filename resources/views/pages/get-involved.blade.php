<x-layouts.app
    title="Get Involved — PHP"
    description="Contribute to PHP: code, documentation, testing, the Foundation and community.">

  <section class="section hero-glow" style="padding-block:clamp(48px,6vw,80px) clamp(28px,3vw,40px);">
    <div class="container center anim-in">
      <p class="eyebrow" style="justify-content:center;">Get involved</p>
      <h1 class="maxw-32 mx-auto">PHP belongs to everyone who builds with it.</h1>
      <p class="lead mx-auto mt-md" style="max-width:52ch;">No company owns PHP. It's maintained by volunteers and Foundation-funded engineers — and there's a way to help that fits the time you have.</p>
    </div>
  </section>

  <section class="section" style="padding-block:clamp(20px,3vw,40px);">
    <div class="container">
      <div class="grid-3 keep-2">
        @php
          $ways = [
            ['icon' => '<path d="M8 6 3 12l5 6M16 6l5 6-5 6"/>', 'title' => 'Contribute code', 'body' => 'Fix bugs, propose RFCs, and review patches on the php-src repository. Good first issues are tagged for newcomers.', 'cta' => 'Browse php-src →', 'href' => 'https://github.com/php/php-src'],
            ['icon' => '<path d="M4 5h16M4 12h12M4 19h16"/>', 'title' => 'Improve the docs', 'body' => 'Every manual page has an edit link. Clearer examples and accurate notes help developers around the world.', 'cta' => 'Docs contributor guide →', 'href' => 'https://github.com/php/doc-en'],
            ['icon' => '<path d="M5 12l4 4 10-10"/>', 'title' => 'Test release candidates', 'body' => 'Run your application against the next RC and report regressions before they reach a stable release.', 'cta' => 'Testing & QA →', 'href' => 'https://qa.php.net/'],
            ['icon' => '<path d="M12 3 4 7v6c0 5 8 8 8 8s8-3 8-8V7Z"/>', 'title' => 'Triage & support', 'body' => 'Help reproduce bug reports and answer questions on the mailing lists and community forums.', 'cta' => 'Mailing lists →', 'href' => 'https://www.php.net/mailing-lists.php'],
            ['icon' => '<path d="M12 21s-7-4.5-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 11c0 5.5-7 10-7 10Z"/>', 'title' => 'Sponsor the Foundation', 'body' => 'Funding lets core developers work on PHP full-time. Companies and individuals both welcome.', 'cta' => 'Become a sponsor →', 'href' => 'https://thephp.foundation/'],
            ['icon' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1-5 5-7 8-7s7 2 8 7"/>', 'title' => 'Run an event', 'body' => "Organize a local user group or conference. We'll help with resources, speakers, and promotion.", 'cta' => 'Community resources →', 'href' => 'https://www.php.net/conferences/index.php'],
          ];
        @endphp
        @foreach ($ways as $w)
          <div class="card">
            <div class="feature-mark"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">{!! $w['icon'] !!}</svg></div>
            <h3>{{ $w['title'] }}</h3>
            <p class="card-meta">{{ $w['body'] }}</p>
            <p class="meta mt-md"><a class="accent" href="{{ $w['href'] }}" target="_blank" rel="noreferrer">{{ $w['cta'] }}</a></p>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- cta --}}
  <section class="section" style="border-top:1px solid var(--border);">
    <div class="container center" style="max-width:600px;">
      <h2>Start where you are.</h2>
      <p class="lead mx-auto" style="margin:16px auto 28px;">Pick one thing — a doc fix, a bug repro, or a sponsorship. Small contributions compound.</p>
      <div class="row-wrap" style="justify-content:center;">
        <a class="btn btn-primary btn-lg" href="https://github.com/php/php-src/issues" target="_blank" rel="noreferrer">Find a good first issue</a>
        <a class="btn btn-ghost btn-arrow" href="https://github.com/php/php-src/blob/master/CONTRIBUTING.md" target="_blank" rel="noreferrer">Read the contributor guide</a>
      </div>
    </div>
  </section>
</x-layouts.app>
