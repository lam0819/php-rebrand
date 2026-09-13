<x-layouts.app
    title="Downloads — PHP"
    description="Download the latest PHP release, source tarballs, Windows builds and checksums.">

  <section class="section hero-glow" style="padding-block:clamp(48px,6vw,80px) clamp(20px,3vw,32px);">
    <div class="container anim-in">
      <p class="eyebrow">Downloads</p>
      <h1 style="max-width:14ch;">Get PHP.</h1>
      <p class="lead mt-md">Source tarballs, Windows builds and checksums for every supported release. Always verify before you install.</p>
    </div>
  </section>

  <section class="section" style="padding-block:clamp(24px,3vw,48px);">
    <div class="container">
      <h2 style="margin-bottom:24px;">Supported releases</h2>

      @foreach ($releases as $i => $release)
        @php($base = 'https://www.php.net/distributions/php-'.$release->version)
        <div class="card" style="margin-bottom:18px;">
          <div class="row-between" style="flex-wrap:wrap;gap:16px;">
            <div class="row" style="gap:16px;">
              <span class="num h2" style="font-size:34px;">{{ $release->version }}</span>
              <div>
                @if ($i === 0)
                  <span class="pill pill-ok">Current · recommended</span>
                @elseif ($release->isSecurityRelease())
                  <span class="pill pill-warn">Security release</span>
                @else
                  <span class="pill pill-ok">Active support</span>
                @endif
                <p class="meta mt-sm" style="margin-top:6px;">PHP {{ $release->branch }} branch · released {{ $release->released_on?->format('d M Y') }}</p>
              </div>
            </div>
            <div class="row-wrap">
              <a class="btn {{ $i === 0 ? 'btn-primary' : 'btn-secondary' }}" href="{{ $base }}.tar.gz" target="_blank" rel="noreferrer">Download .tar.gz</a>
              <a class="btn btn-secondary" href="{{ $base }}.tar.xz" target="_blank" rel="noreferrer">.tar.xz</a>
              <a class="btn btn-ghost btn-arrow" href="{{ route('changelog.show', $release->version) }}" wire:navigate>Changelog</a>
            </div>
          </div>
          @if (! empty($release->sha256['tar.gz']))
            <p class="meta" style="margin-top:16px;word-break:break-all;">sha256 (.tar.gz) · <span style="color:var(--fg);">{{ $release->sha256['tar.gz'] }}</span></p>
          @endif
        </div>
      @endforeach

      <div class="note warn mt-lg" style="max-width:none;">
        <div class="note-label">Verify your download</div>
        Always check the SHA-256 and the PGP signature against the release manager's key before installing.
        <a class="accent" href="https://www.php.net/gpg-keys.php" target="_blank" rel="noreferrer">How to verify →</a>
      </div>
    </div>
  </section>

  <section class="section" style="border-top:1px solid var(--border);">
    <div class="container">
      <h2 style="margin-bottom:20px;">Release support timeline</h2>
      <table class="ds-table">
        <thead><tr><th>Branch</th><th>Initial release</th><th>Active support until</th><th>Security support until</th></tr></thead>
        <tbody>
          <tr><td class="mono accent">8.5</td><td class="num">Nov 2025</td><td class="num">Nov 2027</td><td class="num">Nov 2028</td></tr>
          <tr><td class="mono accent">8.4</td><td class="num">Nov 2024</td><td class="num">Dec 2026</td><td class="num">Dec 2028</td></tr>
          <tr><td class="mono">8.3</td><td class="num">Nov 2023</td><td class="num">Dec 2025</td><td class="num">Dec 2027</td></tr>
          <tr><td class="mono">8.2</td><td class="num">Dec 2022</td><td class="num">Dec 2024</td><td class="num">Dec 2026</td></tr>
        </tbody>
      </table>
      <p class="meta mt-md">Full, authoritative schedule at
        <a class="accent" href="https://www.php.net/supported-versions.php" target="_blank" rel="noreferrer">php.net/supported-versions →</a>
      </p>
    </div>
  </section>
</x-layouts.app>
