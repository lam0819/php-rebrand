@php($tag = $presenter->newsTag($item))
<x-layouts.app
    :title="$item->title.' — PHP News'"
    :description="\Illuminate\Support\Str::of($item->body_html)->stripTags()->squish()->limit(160)->value()">

  <section class="section hero-glow" style="padding-block:clamp(48px,6vw,80px) clamp(16px,2vw,24px);">
    <div class="container anim-in" style="max-width:760px;">
      <a class="btn btn-ghost" href="{{ route('news') }}" wire:navigate style="padding-left:0;display:inline-block;margin-bottom:16px;">← All news</a>
      <div class="row" style="gap:10px;margin-bottom:16px;">
        <span class="pill {{ $tag['pill'] }}">{{ $tag['tag'] }}</span>
        <span class="meta">{{ $item->published_at->format('l, d F Y') }}</span>
      </div>
      <h1 style="max-width:20ch;">{{ $item->title }}</h1>
    </div>
  </section>

  <section class="section" style="padding-block:clamp(8px,1vw,16px) clamp(48px,6vw,80px);">
    <div class="container" style="max-width:760px;">
      <article class="doc-body news-body">
        {!! $item->body_html !!}
      </article>

      @if ($item->via)
        <p class="meta mt-xl" style="border-top:1px solid var(--border);padding-top:20px;">Source: <a class="accent" href="{{ $item->via }}" target="_blank" rel="noreferrer">{{ parse_url($item->via, PHP_URL_HOST) }}</a></p>
      @endif
    </div>
  </section>
</x-layouts.app>
