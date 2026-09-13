/*
 | Tiny progressive-enhancement layer for the server-rendered site.
 | Everything here is delegated from document so it keeps working across
 | Livewire's wire:navigate page swaps (no per-element re-binding needed).
 */

import './search-wasm.js';
import './chat-widget.js';
import './cookie-consent.js';

const STORAGE_KEY = 'php-theme';

function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
    btn.setAttribute(
      'aria-label',
      theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme',
    );
  });
}

function currentTheme() {
  return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
}

document.addEventListener('click', (e) => {
  // Theme toggle
  const toggle = e.target.closest('[data-theme-toggle]');
  if (toggle) {
    const next = currentTheme() === 'dark' ? 'light' : 'dark';
    try {
      localStorage.setItem(STORAGE_KEY, next);
    } catch {}
    applyTheme(next);
    return;
  }

  // Mobile nav toggle
  const navToggle = e.target.closest('[data-nav-toggle]');
  if (navToggle) {
    e.stopPropagation();
    document.querySelector('[data-nav]')?.classList.toggle('open');
    return;
  }

  // Copy code button
  const copy = e.target.closest('[data-copy]');
  if (copy) {
    const code = copy.closest('.code')?.querySelector('pre')?.innerText ?? '';
    navigator.clipboard
      ?.writeText(code)
      .then(() => {
        const original = copy.textContent;
        copy.textContent = 'Copied ✓';
        copy.classList.add('done');
        setTimeout(() => {
          copy.textContent = original;
          copy.classList.remove('done');
        }, 1400);
      })
      .catch(() => {});
    return;
  }

  // Click outside closes the mobile nav
  const nav = document.querySelector('[data-nav]');
  if (nav?.classList.contains('open') && !e.target.closest('.topnav')) {
    nav.classList.remove('open');
  }
});

// "/" focuses the first search input on the page, like a real docs site.
document.addEventListener('keydown', (e) => {
  if (e.key === '/' && !/INPUT|TEXTAREA/.test(e.target.tagName || '')) {
    const search = document.querySelector('[data-search]');
    if (search) {
      e.preventDefault();
      search.focus();
    }
  }
});
