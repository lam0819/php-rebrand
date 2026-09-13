/*
 | Client-side hybrid search for the navbar: InlaySQL's WASM engine, running
 | BM25 + vector retrieval over the prebuilt manual index entirely in the
 | browser. The engine and index are shared with the AI assistant via
 | search-index.js, so they are loaded once.
 |
 | Progressive enhancement: until the engine and index are ready (or if either
 | fails to load) the server-rendered search keeps working untouched. Once
 | ready we take the input over by stopping the event before Livewire sees it.
 */

import { hybridSearch, loadSearchIndex } from './search-index.js';

const LIMIT = 8;

// Friendlier labels than the raw DocBook element names in the index.
const CATEGORY_LABELS = {
  refentry: 'Function',
  chapter: 'Guide',
  reference: 'Reference',
  appendix: 'Appendix',
  book: 'Book',
  set: 'Set',
};

const categoryLabel = (type) => CATEGORY_LABELS[type] ?? (type ? type.charAt(0).toUpperCase() + type.slice(1) : 'Page');

const states = new WeakMap();

function escapeHtml(value) {
  return String(value).replace(/[&<>"']/g, (c) => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
  ));
}

function itemHtml(r) {
  const desc = r.purpose ? `<span class="sr-desc">${escapeHtml(r.purpose)}</span>` : '';
  return `<a class="sr-item" href="/manual/${encodeURIComponent(r.slug)}" wire:navigate>` +
    `<span class="sr-kind" title="${escapeHtml(r.type)}">${escapeHtml(categoryLabel(r.type))}</span>` +
    `<span class="sr-name">${escapeHtml(r.title)}</span>${desc}</a>`;
}

function render(container, results) {
  if (results.length === 0) {
    container.innerHTML = '<div class="sr-empty">No matches.</div>';
    container.hidden = false;
    container.classList.add('show');
    return;
  }

  // Preserve relevance order: groups appear by their best-ranked hit, and the
  // hits inside each group keep the fused ranking.
  const groups = new Map();
  for (const r of results) {
    const label = categoryLabel(r.type);
    if (!groups.has(label)) groups.set(label, []);
    groups.get(label).push(r);
  }

  // Group headers only earn their space when there is more than one category.
  if (groups.size === 1) {
    container.innerHTML = results.map(itemHtml).join('');
  } else {
    let html = '';
    for (const [label, items] of groups) {
      html += `<div class="sr-group"><span class="sr-group-label">${escapeHtml(label)}</span>` +
        `<span class="sr-group-count">${items.length}</span></div>`;
      html += items.map(itemHtml).join('');
    }
    container.innerHTML = html;
  }

  container.hidden = false;
  container.classList.add('show');
}

async function runQuery(state, term) {
  const out = state.out;

  if (term.length < 2) {
    out.hidden = true;
    out.classList.remove('show');
    return;
  }

  try {
    render(out, await hybridSearch(term, { limit: LIMIT, url: state.indexUrl }));
  } catch {
    // Leave the server-rendered fallback in place.
  }
}

function ensureState(root) {
  let state = states.get(root);
  if (state) return state;

  state = {
    ready: false,
    loading: false,
    failed: false,
    timer: null,
    indexUrl: root.dataset.searchIndex || '/manual-search.inlay',
    out: root.querySelector('[data-search-wasm-results]'),
  };
  states.set(root, state);

  const start = () => {
    if (state.ready || state.loading || state.failed || !state.out) return;
    state.loading = true;
    loadSearchIndex(state.indexUrl)
      .then(() => {
        state.ready = true;
        state.loading = false;
        // If the visitor already typed while the engine was loading, answer now.
        const input = root.querySelector('[data-search]');
        if (input && input.value.trim().length >= 2) {
          runQuery(state, input.value.trim());
        }
      })
      .catch((error) => {
        state.loading = false;
        state.failed = true;
        root.classList.remove('wasm-active');
        console.warn('[search-wasm] engine unavailable, using server search:', error);
      });
  };

  root.addEventListener('focusin', start);

  // Capture phase. We claim the input as soon as loading starts — stopping the
  // event before Livewire's listener sees it — because a Livewire re-render
  // would otherwise morph the component and wipe our dropdown. Then we schedule
  // the query here (stopping propagation would also silence a bubble listener
  // on this same root). Only if the engine fails do we hand the input back.
  root.addEventListener('input', (event) => {
    if (!event.target.matches('[data-search]')) return;
    if (state.failed) return;

    event.stopImmediatePropagation();
    root.classList.add('wasm-active');

    if (!state.ready) {
      start();
      return;
    }

    const term = event.target.value.trim();
    clearTimeout(state.timer);
    state.timer = setTimeout(() => runQuery(state, term), 120);
  }, true);

  return state;
}

document.addEventListener('focusin', (event) => {
  const root = event.target.closest?.('[data-search-root]');
  if (root) ensureState(root);
});

// Ensure the root's listeners exist before the first input reaches them.
document.addEventListener('input', (event) => {
  const root = event.target.closest?.('[data-search-root]');
  if (root) ensureState(root);
}, true);

document.addEventListener('click', (event) => {
  document.querySelectorAll('[data-search-root]').forEach((root) => {
    if (root.contains(event.target)) return;
    const state = states.get(root);
    if (!state?.out) return;
    state.out.hidden = true;
    state.out.classList.remove('show');
  });
});
