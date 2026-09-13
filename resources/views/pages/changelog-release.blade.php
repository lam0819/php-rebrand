<x-layouts.app
    :title="'PHP '.$release->version.' Changelog — PHP'"
    :description="'All '.$release->entry_count.' changes in PHP '.$release->version.', sourced from the official php-src NEWS file.'">

  <section class="section hero-glow" style="padding-block:clamp(48px,6vw,80px) clamp(16px,2vw,24px);">
    <div class="container anim-in" style="max-width:820px;">
      <a class="btn btn-ghost" href="{{ route('changelog') }}" wire:navigate style="padding-left:0;display:inline-block;margin-bottom:16px;">← All releases</a>
      <div class="row" style="gap:10px;margin-bottom:14px;align-items:center;flex-wrap:wrap;">
        <span class="pill pill-accent">PHP {{ $release->branch }}</span>
        @if ($release->released_on)
          <span class="meta">{{ $release->released_on->format('l, d F Y') }}</span>
        @else
          <span class="pill pill-warn">In development</span>
        @endif
      </div>
      <h1 class="mono" style="font-size:clamp(30px,5vw,46px);">PHP {{ $release->version }}</h1>
      <p class="lead mt-md">{{ $release->entry_count }} {{ \Illuminate\Support\Str::plural('change', $release->entry_count) }} across {{ count($release->sections) }} {{ \Illuminate\Support\Str::plural('component', count($release->sections)) }}.</p>
    </div>
  </section>

  <section class="section" style="padding-block:clamp(8px,1vw,16px) clamp(48px,6vw,80px);">
    <div class="container" style="max-width:820px;">
      @foreach ($release->sections as $section)
        <div class="changelog-group">
          <h2 class="mono accent">{{ $section['category'] }}</h2>
          <ul class="changelog-list">
            @foreach ($section['entries'] as $entry)
              <li>{!! $presenter->changelogEntry($entry) !!}</li>
            @endforeach
          </ul>
        </div>
      @endforeach

      <div class="row-between mt-xl" style="gap:16px;border-top:1px solid var(--border);padding-top:24px;">
        @if ($newer)
          <a class="btn btn-secondary" href="{{ route('changelog.show', $newer->version) }}" wire:navigate>← {{ $newer->version }}</a>
        @else<span></span>@endif
        @if ($older)
          <a class="btn btn-secondary" href="{{ route('changelog.show', $older->version) }}" wire:navigate>{{ $older->version }} →</a>
        @endif
      </div>
    </div>
  </section>
</x-layouts.app>
