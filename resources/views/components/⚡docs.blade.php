<?php

use App\Docs\Persistence\DocPage;
use App\Docs\Search\DocSearch;
use App\Docs\Support\DocPagePresenter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    #[Url(as: 'q', history: true, keep: true)]
    public string $query = '';

    #[Url(as: 'type', history: true)]
    public string $type = '';

    #[Url(as: 'dir', history: true)]
    public string $dir = '';

    public int $limit = 36;

    public function updated(string $property): void
    {
        // Any filter change resets the window back to the first page of results.
        if ($property !== 'limit') {
            $this->limit = 36;
        }
    }

    public function filterCategory(string $term): void
    {
        $this->query = $term;
        $this->type = '';
        $this->dir = '';
        $this->limit = 36;
    }

    public function loadMore(): void
    {
        $this->limit += 36;
    }

    /**
     * The current page of results plus the matching total. A text query routes
     * through the ranked FTS5 index; type-only or directory-scoped browsing (and
     * any FTS fallback) goes through Eloquent.
     *
     * @return array{total: int, items: list<array{slug: string, name: string, kind: string, desc: string}>}
     */
    #[Computed]
    public function result(): array
    {
        $term = trim($this->query);
        $search = app(DocSearch::class);

        if ($term !== '' && $this->dir === '' && $search->available()) {
            $items = $search->summaries($term, $this->limit, $this->type ?: null);
            $total = $search->count($term, $this->type ?: null);

            if ($items !== null && $total !== null) {
                return ['total' => $total, 'items' => $items];
            }
        }

        return $this->eloquentResult();
    }

    /**
     * @return array{total: int, items: list<array{slug: string, name: string, kind: string, desc: string}>}
     */
    private function eloquentResult(): array
    {
        $presenter = app(DocPagePresenter::class);
        $builder = $this->baseQuery();

        $items = (clone $builder)
            ->orderByRaw('case when type = ? then 0 else 1 end', ['refentry'])
            ->orderBy('title')
            ->limit($this->limit)
            ->get(['slug', 'title', 'type', 'metadata'])
            ->map(fn (DocPage $p) => $presenter->summary($p))
            ->values()
            ->all();

        return ['total' => (clone $builder)->count(), 'items' => $items];
    }

    /** @return \Illuminate\Database\Eloquent\Builder<DocPage> */
    private function baseQuery()
    {
        $builder = DocPage::query();

        if (trim($this->query) !== '') {
            $like = '%'.trim($this->query).'%';
            $builder->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('slug', 'like', $like));
        }
        if ($this->type !== '') {
            $builder->where('type', $this->type);
        }
        if ($this->dir !== '') {
            $builder->where('source_path', 'like', $this->dir.'/%');
        }

        return $builder;
    }

    /** @return array<string, int> */
    #[Computed]
    public function typeCounts(): array
    {
        return DocPage::query()
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->orderByDesc('total')
            ->pluck('total', 'type')
            ->all();
    }
}; ?>

<div>
    @php
        $categories = ['array', 'string', 'preg', 'json', 'date', 'math', 'file', 'pdo', 'mb', 'ctype', 'hash', 'curl'];
        $typeLabels = ['refentry' => 'Functions', 'chapter' => 'Guides', 'reference' => 'References', 'appendix' => 'Appendices'];
        $result = $this->result;
        $total = $result['total'];
        $items = $result['items'];
    @endphp

    <div class="searchbar lg" style="margin-bottom: 18px">
        <span class="s-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
        </span>
        <input type="search" data-search placeholder="Search 11,000+ manual pages…" aria-label="Search the manual"
               autocomplete="off" wire:model.live.debounce.250ms="query" />
    </div>

    {{-- type filters --}}
    <div class="row-wrap" style="gap: 8px; margin-bottom: 14px">
        <button type="button" class="tag {{ $type === '' ? 'active-tag' : '' }}" wire:click="$set('type', '')">All</button>
        @foreach ($typeLabels as $value => $label)
            @if (! empty($this->typeCounts[$value]))
                <button type="button" class="tag {{ $type === $value ? 'active-tag' : '' }}" wire:click="$set('type', '{{ $value }}')">
                    {{ $label }} <span class="meta" style="margin-left: 6px">{{ number_format($this->typeCounts[$value]) }}</span>
                </button>
            @endif
        @endforeach
    </div>

    {{-- category chips --}}
    <div class="row-wrap" style="gap: 8px; margin-bottom: 22px">
        @foreach ($categories as $cat)
            <button type="button" class="tag {{ $query === $cat ? 'active-tag' : '' }}" wire:click="filterCategory('{{ $cat }}')">{{ $cat }}</button>
        @endforeach
    </div>

    <p class="meta" style="margin-bottom: 16px">
        {{ number_format($total) }} {{ Str::plural('page', $total) }}@if (trim($query) !== '') matching “{{ $query }}”@endif
    </p>

    <div class="grid-auto" wire:loading.class="is-loading">
        @forelse ($items as $p)
            <a class="card card-link" href="/manual/{{ $p['slug'] }}" wire:navigate wire:key="{{ $p['slug'] }}">
                <div class="row-between" style="gap: 8px; margin-bottom: 6px">
                    <h3 class="mono accent" style="font-size: 16px">{{ $p['name'] }}</h3>
                    <span class="pill pill-accent" style="font-size: 9px">{{ $p['kind'] }}</span>
                </div>
                @if ($p['desc'])<p class="card-meta">{{ Str::limit($p['desc'], 96) }}</p>@endif
            </a>
        @empty
            <p class="lead">No pages match. Try a different term.</p>
        @endforelse
    </div>

    @if (count($items) < $total)
        <div class="center mt-lg">
            <button type="button" class="btn btn-secondary" wire:click="loadMore">
                Load more <span class="meta" style="margin-left: 6px">{{ count($items) }} / {{ number_format($total) }}</span>
            </button>
        </div>
    @endif
</div>
