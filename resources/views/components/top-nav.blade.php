@php
    $nav = [
        ['href' => '/docs', 'label' => 'Documentation', 'match' => 'docs*'],
        ['href' => '/downloads', 'label' => 'Downloads', 'match' => 'downloads*'],
        ['href' => '/news', 'label' => 'News', 'match' => 'news*'],
        ['href' => '/get-involved', 'label' => 'Get Involved', 'match' => 'get-involved*'],
    ];
@endphp

<header class="topnav">
  <div class="container-wide topnav-inner">
    <a class="brand" href="/" aria-label="PHP home" wire:navigate>
      <x-brand-mark />
      <span>php<span class="ver">8.5</span></span>
    </a>

    <nav data-nav>
      @foreach ($nav as $item)
        <a href="{{ $item['href'] }}" wire:navigate
           class="{{ request()->is($item['match']) ? 'active' : '' }}">{{ $item['label'] }}</a>
      @endforeach
    </nav>

    <span class="spacer"></span>

    <div class="nav-tools">
      <livewire:search />

      <button type="button" class="icon-btn" data-theme-toggle aria-label="Switch theme">
        <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
        <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
      </button>

      <a class="btn btn-primary btn-sm hide-sm" href="/downloads" wire:navigate>Download</a>

      <button type="button" class="icon-btn nav-toggle" data-nav-toggle aria-label="Open menu">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
      </button>
    </div>
  </div>
</header>
