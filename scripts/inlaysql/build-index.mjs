#!/usr/bin/env node
/*
 | Build the browser search index: one InlaySQL file holding every manual page
 | with a BM25 index over its text and an HNSW index over the trigram-hashed
 | vector. The vectors come from the WASM module's own `embed()` — the exact
 | function the browser calls at query time — so corpus and query always match.
 |
 | Run via `php artisan search:build` or directly:
 |   node scripts/inlaysql/build-index.mjs \
 |     --input=storage/app/search-export.ndjson \
 |     --output=storage/app/manual-search.inlay \
 |     --wasm-dir=storage/app/inlaysql/pkg --dim=256 --int8 --batch=50
 */
import { createReadStream, readFileSync, writeFileSync } from 'node:fs';
import { createInterface } from 'node:readline';
import { pathToFileURL } from 'node:url';
import { resolve } from 'node:path';

const args = Object.fromEntries(
  process.argv.slice(2).map((arg) => {
    const [key, ...rest] = arg.replace(/^--/, '').split('=');
    return [key, rest.length ? rest.join('=') : true];
  }),
);

const input = resolve(args.input ?? 'storage/app/search-export.ndjson');
const output = resolve(args.output ?? 'storage/app/manual-search.inlay');
const wasmDir = resolve(args['wasm-dir'] ?? 'storage/app/inlaysql/pkg');
const dim = Number(args.dim ?? 256);
const int8 = args.int8 !== undefined && args.int8 !== 'false';
const batchSize = Number(args.batch ?? 50);

const moduleUrl = pathToFileURL(resolve(wasmDir, 'inlaysql_wasm.js')).href;
const wasmBytes = readFileSync(resolve(wasmDir, 'inlaysql_wasm_bg.wasm'));
const engine = await import(moduleUrl);
const { Database, embed } = engine;
await engine.default({ module_or_path: wasmBytes });

// PHP/JS JSON drops the ".0" from whole floats, and the engine's vector binder
// rejects an integer-typed JSON element ("must hold only numbers"). Nudge exact
// integers by an epsilon that cannot change ranking.
const vector = (text) => Array.from(embed(text, dim), (v) => (Number.isInteger(v) ? v + 1e-7 : v));

// The title is repeated so a function-name match outranks a body mention of
// the same words under BM25's length normalisation.
const body = (row) => {
  const title = typeof row.title === 'string' ? row.title.trim() : '';
  return [title, title, title, row.purpose, row.excerpt]
    .map((part) => (typeof part === 'string' ? part.trim() : ''))
    .filter(Boolean)
    .join('. ')
    .slice(0, 4000);
};

const db = new Database();
db.execute(`CREATE TABLE pages (
  id INTEGER PRIMARY KEY,
  slug TEXT,
  title TEXT,
  type TEXT,
  purpose TEXT,
  body TEXT,
  embedding VECTOR(${dim}${int8 ? ', INT8' : ''})
)`);
db.execute('CREATE INDEX pages_body ON pages (body)');
db.execute('CREATE INDEX pages_embedding ON pages (embedding)');

const lines = createInterface({ input: createReadStream(input), crlfDelay: Infinity });

let batch = [];
let count = 0;
const flush = () => {
  if (batch.length === 0) return;
  const tuples = [];
  const params = [];
  let p = 1;
  for (const row of batch) {
    tuples.push(`(?${p++}, ?${p++}, ?${p++}, ?${p++}, ?${p++}, ?${p++})`);
    params.push(row.slug, row.title, row.type, row.purpose, row.body, row.embedding);
  }
  db.execute(`INSERT INTO pages (slug, title, type, purpose, body, embedding) VALUES ${tuples.join(',')}`, JSON.stringify(params));
  count += batch.length;
  batch = [];
  if (count % 1000 === 0) process.stderr.write(`  indexed ${count} pages\n`);
};

for await (const line of lines) {
  const trimmed = line.trim();
  if (trimmed === '') continue;
  const row = JSON.parse(trimmed);
  const text = body(row);
  batch.push({
    slug: row.slug,
    title: row.title,
    type: row.type ?? '',
    purpose: row.purpose ?? '',
    body: text,
    embedding: vector(text),
  });
  if (batch.length >= batchSize) flush();
}
flush();

process.stderr.write('  building indexes…\n');
db.execute('REINDEX');

process.stderr.write('  checkpointing…\n');
try { db.execute('CHECKPOINT'); } catch { /* not required on every build */ }

const bytes = db.export();
writeFileSync(output, bytes);
db.free();

process.stderr.write(`search index: ${count} pages, dim ${dim}${int8 ? ' int8' : ''}, ${(bytes.length / 1024 / 1024).toFixed(1)} MiB → ${output}\n`);
process.stdout.write(JSON.stringify({ pages: count, bytes: bytes.length, output }) + '\n');
