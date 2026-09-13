<x-layouts.app
    title="Changelog — PHP"
    description="Every PHP release and its bug fixes, sourced from the official php-src NEWS files.">

  <section class="section hero-glow" style="padding-block:clamp(48px,6vw,80px) clamp(20px,3vw,32px);">
    <div class="container anim-in">
      <p class="eyebrow">Changelog</p>
      <h1 style="max-width:16ch;">Every release, every fix.</h1>
      <p class="lead mt-md" style="max-width:60ch;">Built straight from the <code class="inline">NEWS</code> files in
        <a class="accent" href="https://github.com/php/php-src" target="_blank" rel="noreferrer">php/php-src</a> — the same source php.net's ChangeLog is generated from.</p>
    </div>
  </section>

  <section class="section" style="padding-block:clamp(16px,2vw,32px) clamp(48px,6vw,80px);">
    <div class="container">
      @forelse ($branches as $branch => $releases)
        <div style="margin-bottom:48px;">
          <div class="row-between" style="margin-bottom:16px;flex-wrap:wrap;gap:12px;">
            <h2 class="mono accent" style="font-size:clamp(22px,3vw,30px);">PHP {{ $branch }}</h2>
            <span class="meta">{{ $releases->count() }} {{ \Illuminate\Support\Str::plural('release', $releases->count()) }}</span>
          </div>
          <div class="grid-3 keep-2">
            @foreach ($releases as $release)
              <a class="card card-link" href="{{ route('changelog.show', $release->version) }}" wire:navigate>
                <div class="row-between" style="margin-bottom:10px;">
                  <span class="num h3">{{ $release->version }}</span>
                  <span class="meta">{{ $release->released_on?->format('d M Y') }}</span>
                </div>
                <p class="card-meta">{{ $release->entry_count }} {{ \Illuminate\Support\Str::plural('change', $release->entry_count) }}</p>
              </a>
            @endforeach
          </div>
        </div>
      @empty
        <p class="lead">No changelog imported yet. Run <code class="inline">php artisan web:sync</code> to fetch the php-src NEWS files.</p>
      @endforelse
    </div>
  </section>
</x-layouts.app>
