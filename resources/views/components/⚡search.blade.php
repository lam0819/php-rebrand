<?php

use App\Docs\Persistence\DocPage;
use App\Docs\Search\DocSearch;
use App\Docs\Support\DocPagePresenter;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $query = '';

    /**
     * Live, ranked matches for the current query (title-prefix first).
     *
     * @return list<array{slug: string, name: string, kind: string, desc: string}>
     */
    #[Computed]
    public function results(): array
    {
        $term = trim($this->query);

        if (mb_strlen($term) < 2) {
            return [];
        }

        // Prefer the ranked FTS5 index; fall back to LIKE when it's unavailable.
        $fts = app(DocSearch::class)->summaries($term, 8);
        if ($fts !== null) {
            return $fts;
        }

        // Bound params keep this injection-safe; % and _ stay as wildcards so
        // "str_replace" still matches.
        $like = '%'.$term.'%';
        $presenter = app(DocPagePresenter::class);

        return DocPage::query()
            ->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('slug', 'like', $like))
            ->orderByRaw('case when title like ? then 0 else 1 end', [$term.'%'])
            ->orderBy('title')
            ->limit(8)
            ->get(['slug', 'title', 'type', 'metadata'])
            ->map(fn (DocPage $p) => $presenter->summary($p))
            ->values()
            ->all();
    }
}; ?>

<div
  class="searchbar"
  data-search-root
  data-search-index="{{ route('search.index') }}"
  style="position: relative; width: 260px"
  @click.outside="$wire.query = ''"
>
  <span class="s-icon">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
  </span>
  <input
    type="search"
    data-search
    placeholder="Search the manual…"
    aria-label="Search the manual"
    autocomplete="off"
    wire:model.live.debounce.250ms="query"
    style="padding-block: 9px"
  />
  <kbd>/</kbd>

  @if (trim($query) !== '')
    <div class="search-results show">
      @forelse ($this->results as $r)
        <a class="sr-item" href="/manual/{{ $r['slug'] }}" wire:navigate wire:key="{{ $r['slug'] }}">
          <span class="sr-kind">{{ $r['kind'] }}</span>
          <span class="sr-name">{{ $r['name'] }}</span>
          @if ($r['desc'])<span class="sr-desc">{{ $r['desc'] }}</span>@endif
        </a>
      @empty
        <div class="sr-empty">No matches for “{{ $query }}”.</div>
      @endforelse
    </div>
  @endif

  {{-- Filled by resources/js/search-wasm.js once the in-browser engine is ready. --}}
  <div class="search-results search-wasm-results" data-search-wasm-results wire:ignore hidden></div>
</div>
