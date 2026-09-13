<x-layouts.app
    title="News — PHP"
    description="Every stable release, security advisory, and milestone from the PHP project — newest first.">

  <section class="section hero-glow" style="padding-block:clamp(48px,6vw,80px) clamp(20px,3vw,32px);">
    <div class="container anim-in">
      <p class="eyebrow">News</p>
      <h1 style="max-width:14ch;">Releases, advisories &amp; community.</h1>
      <p class="lead mt-md">Every stable release, security advisory, and milestone from the PHP project — newest first.</p>
    </div>
  </section>

  @if ($featured)
    @php($featuredTag = $presenter->newsTag($featured))
    {{-- featured --}}
    <section class="section" style="padding-block:clamp(16px,2vw,32px);">
      <div class="container">
        <a class="card card-link hero-glow" href="{{ route('news.show', $featured->entry_id) }}" wire:navigate style="padding:clamp(28px,4vw,44px);display:block;">
          <div class="row" style="gap:10px;margin-bottom:14px;"><span class="pill {{ $featuredTag['pill'] }}">{{ $featuredTag['tag'] }}</span><span class="meta">{{ $featured->published_at->format('d M Y') }}</span></div>
          <h2 class="maxw-48">{{ $featured->title }}</h2>
          <p class="lead mt-md" style="max-width:64ch;">{{ \Illuminate\Support\Str::of($featured->body_html)->stripTags()->squish()->limit(220) }}</p>
          <span class="btn btn-ghost btn-arrow mt-md" style="padding-left:0;">Read the announcement</span>
        </a>
      </div>
    </section>
  @endif

  <section class="section" style="padding-block:clamp(16px,2vw,32px);">
    <div class="container">
      <h2 style="margin-bottom:8px;">All announcements</h2>
      @forelse ($items as $item)
        @php($tag = $presenter->newsTag($item))
        <a class="log-row card-link" href="{{ route('news.show', $item->entry_id) }}" wire:navigate>
          <span class="meta">{{ $item->published_at->format('d M Y') }}</span>
          <div>
            <h3>{{ $item->title }}</h3>
            <p>{{ \Illuminate\Support\Str::of($item->body_html)->stripTags()->squish()->limit(180) }}</p>
          </div>
          <span class="pull pill {{ $tag['pill'] }}">{{ $tag['tag'] }}</span>
        </a>
      @empty
        <p class="lead">No announcements yet. Run <code class="inline">php artisan web:sync</code> to import the archive.</p>
      @endforelse

      @if ($items->hasPages())
        <div class="row-between mt-xl" style="gap:16px;">
          @if ($items->onFirstPage())
            <span class="btn btn-secondary" style="opacity:.4;pointer-events:none;">← Newer</span>
          @else
            <a class="btn btn-secondary" href="{{ $items->previousPageUrl() }}" wire:navigate>← Newer</a>
          @endif
          <span class="meta">Page {{ $items->currentPage() }} of {{ $items->lastPage() }}</span>
          @if ($items->hasMorePages())
            <a class="btn btn-secondary" href="{{ $items->nextPageUrl() }}" wire:navigate>Older →</a>
          @else
            <span class="btn btn-secondary" style="opacity:.4;pointer-events:none;">Older →</span>
          @endif
        </div>
      @endif
    </div>
  </section>
</x-layouts.app>
