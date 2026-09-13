@props([
    'title' => 'PHP — A fast, pragmatic language for the web',
    'description' => 'PHP is a popular general-purpose scripting language especially suited to web development. Fast, flexible and pragmatic.',
    'canonical' => null,
])

@php
    $canonicalUrl = $canonical ?? url()->current();
@endphp

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <title>{{ $title }}</title>
  <meta name="description" content="{{ $description }}" />
  <link rel="canonical" href="{{ $canonicalUrl }}" />

  {{-- Open Graph / Twitter --}}
  <meta property="og:type" content="website" />
  <meta property="og:title" content="{{ $title }}" />
  <meta property="og:description" content="{{ $description }}" />
  <meta property="og:url" content="{{ $canonicalUrl }}" />
  <meta name="twitter:card" content="summary" />

  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2032%2032'%3E%3Crect%20width='32'%20height='32'%20rx='7'%20fill='%23574fd6'/%3E%3Cg%20fill='none'%20stroke='%23fff'%20stroke-width='2.2'%20stroke-linecap='round'%20stroke-linejoin='round'%3E%3Cpath%20d='M9.5%2024v-7a7%207%200%200%201%2014%200v7'/%3E%3Cpath%20d='M9.5%2017.5c-2.7.2-4.2-1.6-4-4.2.2-2.6%202.4-3.2%204-2'/%3E%3Cpath%20d='M20%2024c0%203%20.5%205%202.6%205.5%201.9.4%203-1%202.5-2.7'/%3E%3Cpath%20d='M11%2024v2.4M15.5%2024v2.4'/%3E%3C/g%3E%3Ccircle%20cx='13.5'%20cy='15'%20r='1.2'%20fill='%23fff'/%3E%3C/svg%3E" />
  <link rel="manifest" href="/manifest.webmanifest" />
  <meta name="theme-color" content="#574fd6" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet" />

  <script>
    // Set theme before first paint to avoid a flash.
    (function () {
      try {
        var stored = localStorage.getItem('php-theme');
        if (stored) document.documentElement.setAttribute('data-theme', stored);
        else if (window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches)
          document.documentElement.setAttribute('data-theme', 'dark');
      } catch (e) {}
    })();
  </script>

  {{ $head ?? '' }}

  @vite(['resources/css/site.css', 'resources/js/site.js'])
  @livewireStyles
</head>
<body>
  <x-top-nav />
  <main id="content">
    {{ $slot }}
  </main>
  <x-site-footer />

  @livewireScripts
  <script>
    // Register the service worker for PWA / offline-of-visited-pages.
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function () {
        navigator.serviceWorker.register('/sw.js').catch(function () {});
      });
    }
  </script>
</body>
</html>
