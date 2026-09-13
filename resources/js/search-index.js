/*
 | The shared InlaySQL WASM search index: one engine and one opened database per
 | page, reused by the navbar search and the AI assistant so the ~5 MB artifact
 | is fetched and parsed only once.
 */

const DEFAULT_INDEX = '/manual-search.inlay';
const WASM_BASE = '/inlaysql';

// InlaySQL's vector binder rejects JSON integers, and JSON.stringify drops the
// ".0" from whole floats. Nudge exact integers without changing ranking.
const fixVector = (values) => Array.from(values, (v) => (Number.isInteger(v) ? v + 1e-7 : v));

let pending = null;

async function loadEngine() {
  const mod = await import(/* @vite-ignore */ `${WASM_BASE}/inlaysql_wasm.js`);
  await mod.default({ module_or_path: `${WASM_BASE}/inlaysql_wasm_bg.wasm` });
  return mod;
}

export function loadSearchIndex(url = DEFAULT_INDEX) {
  if (!pending) {
    pending = (async () => {
      const [engine, response] = await Promise.all([
        loadEngine(),
        fetch(url, { headers: { Accept: 'application/octet-stream' } }),
      ]);

      if (!response.ok) throw new Error(`search index ${response.status}`);

      const bytes = new Uint8Array(await response.arrayBuffer());
      const db = engine.Database.open(bytes);

      const table = JSON.parse(db.schema()).tables.find((t) => t.table === 'pages');
      const vector = table?.columns.find((c) => String(c.type).startsWith('VECTOR('));
      const match = vector ? /VECTOR\((\d+)/.exec(String(vector.type)) : null;
      const dim = match ? Number(match[1]) : 0;
      if (!dim) throw new Error('search index has no vector column');

      return { db, embed: engine.embed, dim };
    })();

    // Let a failed load be retried on the next interaction.
    pending.catch(() => {
      pending = null;
    });
  }

  return pending;
}

/**
 * Hybrid (vector + BM25) lookup over the manual.
 *
 * @returns {Promise<Array<{slug: string, title: string, type: string, purpose: string, body: string}>>}
 */
export async function hybridSearch(term, { limit = 8, url = DEFAULT_INDEX } = {}) {
  const { db, embed, dim } = await loadSearchIndex(url);
  const embedding = fixVector(Array.from(embed(term, dim)));

  const sql =
    'SELECT slug, title, type, purpose, body, ' +
    'fuse(vector_score(embedding, ?1), bm25_score(body, ?2)) AS score ' +
    `FROM pages ORDER BY score DESC LIMIT ${Math.max(1, Math.floor(limit))}`;

  const result = JSON.parse(db.query(sql, JSON.stringify([embedding, term])));

  return result.rows.map(([slug, title, type, purpose, body]) => ({ slug, title, type, purpose, body }));
}
