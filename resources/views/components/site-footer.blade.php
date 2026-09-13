@php
    $columns = [
        ['title' => 'Docs', 'links' => [
            ['label' => 'Manual', 'href' => '/docs'],
            ['label' => 'Function reference', 'href' => '/docs?type=refentry'],
            ['label' => 'Language reference', 'href' => '/manual/language-basic-syntax'],
            ['label' => 'Migration guides', 'href' => '/docs?q=migrating'],
        ]],
        ['title' => 'Get PHP', 'links' => [
            ['label' => 'Downloads', 'href' => '/downloads'],
            ['label' => 'Changelog', 'href' => '/changelog'],
            ['label' => 'Windows builds', 'href' => 'https://windows.php.net/download/', 'external' => true],
            ['label' => 'Release schedule', 'href' => 'https://www.php.net/supported-versions.php', 'external' => true],
        ]],
        ['title' => 'Community', 'links' => [
            ['label' => 'Get involved', 'href' => '/get-involved'],
            ['label' => 'The Foundation', 'href' => 'https://thephp.foundation/', 'external' => true],
            ['label' => 'Mailing lists', 'href' => 'https://www.php.net/mailing-lists.php', 'external' => true],
            ['label' => 'News & events', 'href' => '/news'],
            ['label' => 'Privacy & cookies', 'href' => '/privacy'],
        ]],
        ['title' => 'Social', 'links' => [
            ['label' => 'Mastodon', 'href' => 'https://phpc.social/@php', 'external' => true],
            ['label' => 'Twitter / X', 'href' => 'https://twitter.com/official_php', 'external' => true],
            ['label' => 'GitHub', 'href' => 'https://github.com/php/php-src', 'external' => true],
            ['label' => 'doc-en source', 'href' => 'https://github.com/php/doc-en', 'external' => true],
        ]],
    ];
@endphp

<footer class="pagefoot">
  <div class="container-wide">
    <div class="foot-grid">
      <div class="foot-col">
        <a class="brand" href="/" style="margin-bottom: 14px" wire:navigate>
          <x-brand-mark />
          <span>php<span class="ver">8.5</span></span>
        </a>
        <p class="meta maxw-32">
          A general-purpose scripting language especially suited to web development. Free and
          open source since 1995.
        </p>
      </div>
      @foreach ($columns as $col)
        <div class="foot-col">
          <h4>{{ $col['title'] }}</h4>
          @foreach ($col['links'] as $link)
            @if ($link['external'] ?? false)
              <a href="{{ $link['href'] }}" target="_blank" rel="noreferrer">{{ $link['label'] }}</a>
            @else
              <a href="{{ $link['href'] }}" wire:navigate>{{ $link['label'] }}</a>
            @endif
          @endforeach
        </div>
      @endforeach
    </div>
    <div class="foot-bottom">
      <span class="meta">© 2001–2026 The PHP Group · Documentation licensed under CC-BY 3.0</span>
      <span class="meta">My PHP.net · Contact · Privacy policy</span>
    </div>
  </div>
</footer>
