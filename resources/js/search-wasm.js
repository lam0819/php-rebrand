/*
 | Client-side hybrid search: InlaySQL's WASM engine, loaded lazily the first
 | time someone uses the search box, running BM25 + vector retrieval over the
 | prebuilt manual index entirely in the browser.
 |
 | Progressive enhancement: until the engine and index are ready (or if either
 | fails to load) the existing server-rendered search keeps working untouched.
 | Once ready we take over the input by stopping the event before Livewire sees
 | it and render our own results into a dedicated container.
 */

const DEFAULT_INDEX = '/manual-search.inlay';
const WASM_BASE = '/inlaysql';
const LIMIT = 8;

const states = new WeakMap();

function fixVector(values) {
  // InlaySQL's vector binder rejects JSON integers, and JSON.stringify drops
  // the ".0" from whole floats. Nudge exact integers without changing ranking.
  return Array.from(values, (v) => (Number.isInteger(v) ? v + 1e-7 : v));
}

async function loadEngine() {
  const mod = await import(/* @vite-ignore */ `${WASM_BASE}/inlaysql_wasm.js`);
  await mod.default({ module_or_path: `${WASM_BASE}/inlaysql_wasm_bg.wasm` });
  return mod;
}

async function loadIndex(root) {
  const url = root.dataset.searchIndex || DEFAULT_INDEX;
  const response = await fetch(url, { headers: { Accept: 'application/octet-stream' } });
  if (!response.ok) throw new Error(`index ${response.status}`);

  const engine = await loadEngine();
  const { Database } = engine;
  const bytes = new Uint8Array(await response.arrayBuffer());
  const db = Database.open(bytes);

  const table = JSON.parse(db.schema()).tables.find((t) => t.table === 'pages');
  const vector = table?.columns.find((c) => String(c.type).startsWith('VECTOR('));
  const match = vector ? /VECTOR\((\d+)/.exec(String(vector.type)) : null;
  const dim = match ? Number(match[1]) : 0;
  if (!dim) throw new Error('no vector column');

  return { db, embed: engine.embed, dim };
}

function escapeHtml(value) {
  return String(value).replace(/[&<>"']/g, (c) => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
  ));
}

function render(container, results) {
  if (results.length === 0) {
    container.innerHTML = '<div class="sr-empty">No matches.</div>';
  } else {
    container.innerHTML = results
      .map((r) => {
        const desc = r.purpose ? `<span class="sr-desc">${escapeHtml(r.purpose)}</span>` : '';
        return `<a class="sr-item" href="/manual/${encodeURIComponent(r.slug)}" wire:navigate>` +
          `<span class="sr-kind">${escapeHtml(r.type)}</span>` +
          `<span class="sr-name">${escapeHtml(r.title)}</span>${desc}</a>`;
      })
      .join('');
  }
  container.hidden = false;
  container.classList.add('show');
}

function runQuery(state, term) {
  const { db, embed, dim, out } = state;

  if (term.length < 2) {
    out.hidden = true;
    out.classList.remove('show');
    return;
  }

  let embedding;
  try {
    embedding = fixVector(Array.from(embed(term, dim)));
  } catch {
    return;
  }

  let rows = [];
  try {
    const sql =
      'SELECT slug, title, type, purpose, ' +
      'fuse(vector_score(embedding, ?1), bm25_score(body, ?2)) AS score ' +
      `FROM pages ORDER BY score DESC LIMIT ${LIMIT}`;
    const result = JSON.parse(db.query(sql, JSON.stringify([embedding, term])));
    rows = result.rows.map(([slug, title, type, purpose]) => ({ slug, title, type, purpose }));
  } catch {
    return;
  }

  render(out, rows);
}

function ensureState(root) {
  let state = states.get(root);
  if (state) return state;

  state = { ready: false, loading: false, timer: null, out: root.querySelector('[data-search-wasm-results]') };
  states.set(root, state);

  const start = () => {
    if (state.ready || state.loading || !state.out) return;
    state.loading = true;
    loadIndex(root)
      .then((engine) => {
        Object.assign(state, engine, { ready: true, loading: false });
        root.classList.add('wasm-active');
      })
      .catch(() => {
        state.loading = false;
      });
  };

  root.addEventListener('focusin', start);

  // Capture phase, so once ready we can stop the event before Livewire's own
  // listener sees it — then schedule our query in the same handler (stopping
  // propagation would also silence a separate bubble listener on the root).
  root.addEventListener('input', (event) => {
    if (!event.target.matches('[data-search]')) return;

    if (!state.ready) {
      start();
      return;
    }

    event.stopImmediatePropagation();
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
