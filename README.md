# PHP, evolved — a modern rebrand of php.net

A fast, faithful, modern rebuild of [php.net](https://www.php.net) — the manual,
the news, and the downloads — rendered from the **official PHP source repositories**
so it stays correct and current without hand-maintained content.

This is **not** a fork of the official site. It's a from-scratch Laravel
application whose content is parsed directly from upstream, wrapped in a modern
design, full-text search, a PWA, and AI-agent-friendly endpoints.

- ⚡ **Server-rendered** (Livewire v4 + Blade) — every page is crawlable HTML, fast to load and display.
- 🔎 **Instant search** over the whole manual (SQLite FTS5, bm25-ranked).
- 📦 **Single SQLite artifact** — the whole manual ships as one read-only file; no database server needed in production.
- 🤖 **AI-friendly** — `/llms.txt` index + a Markdown view of every page at `/manual/{slug}.md`.
- 📱 **PWA** — installable, works offline for visited pages.

---

## Where the truth comes from

Everything on the site is derived from two official repositories. We never
hand-author manual content, news, or release numbers — we parse the source and
rebuild. When upstream changes, a sync run brings the site up to date.

| Content | Single source of truth | How we use it |
|---|---|---|
| **The manual** (all ~11,000 pages) | [`php/doc-en`](https://github.com/php/doc-en) — the official DocBook XML | Parsed directly into HTML + Markdown. We do **not** use `php/doc-base` or `php/phd`. |
| **News & announcements** | [`php/web-php`](https://github.com/php/web-php) → `archive/entries/*.xml` (Atom) | Full archive imported, newest-first, with a detail page per entry. |
| **Releases & downloads** | [`php/web-php`](https://github.com/php/web-php) → `include/version.inc` | Current release of each active branch, with the **real sha256 checksums**. |

> The one deliberate exception is the downloads "support timeline" (EOL
> projection dates), which isn't present in `version.inc`; that table is static
> and links out to the authoritative `php.net/supported-versions`.

---

## How it's built

```
                       ┌─────────────────────────────────────────────┐
  php/doc-en  ───────► │  docs:fetch → docs:import → docs:reindex     │ ─┐
  (DocBook XML)        │  parse → render HTML/MD → docs_pages + FTS5  │  │
                       └─────────────────────────────────────────────┘  │
                                                                         ├─► one SQLite file
                       ┌─────────────────────────────────────────────┐  │   (database/database.sqlite)
  php/web-php  ──────► │  web:fetch → web:sync                        │ ─┘
  (Atom + PHP config)  │  parse Atom + version.inc → news / releases  │
                       └─────────────────────────────────────────────┘
                                          │
                                          ▼
                   Laravel (Livewire v4 + Blade) renders every page,
                   reading only from the prebuilt SQLite file at runtime.
```

The import pipeline is layered and source-agnostic after the parse step — see
[`docs/Architecture.md`](docs/Architecture.md) for the full design.

### Tech stack

| Concern | Choice |
|---|---|
| Framework | Laravel 12, PHP 8.4 (strict types throughout) |
| Frontend | Livewire v4 + Blade single-file components, `livewire/blaze` compile-time folding |
| Styling | Hand-written CSS design system (OKLch tokens), built with Vite |
| Database | SQLite — a single read-only file in production |
| Search | SQLite FTS5 (bm25), with a LIKE fallback |
| Tests / Analysis / Style | Pest · PHPStan (max level, Larastan) · Laravel Pint |
| Container | `serversideup/php:8.4-fpm-nginx` |

---

## Project structure

```
app/
  Docs/                 The manual pipeline (parse php/doc-en → docs_pages + FTS)
    Contracts/          Interfaces for every stage (the seams)
    Importer/ Parser/   File discovery, XML loading, per-type parsers
    Rendering/          DocBookHtmlRenderer (full body → HTML), HtmlToMarkdown
    Persistence/        DocPage model + Eloquent repository
    Search/             DocSearch (FTS5)
    Support/            DocPagePresenter, value objects
  Web/                  The site-content pipeline (parse php/web-php)
    Parsing/            NewsEntryParser (Atom), ReleaseConfigParser (version.inc)
    Models/             NewsItem, PhpRelease
    Support/            WebContentPresenter
    WebContentImporter.php
  Console/Commands/     docs:* and web:* artisan commands
  Http/Controllers/     Home, Manual, News, Downloads, Llms, Pwa, Sitemap
resources/
  views/                Blade pages + components (⚡-prefixed = Livewire SFCs)
  css/ js/              Design system + vanilla JS, bundled by Vite
config/
  docs.php  web.php     Upstream repo URLs + local checkout paths
database/migrations/    docs_pages, docs_fts, news_items, php_releases
docs/Architecture.md    Pipeline design, layer-by-layer
Dockerfile  docker-compose.yml
```

---

## Getting started (local)

Requires PHP 8.4, Composer, and Node 20+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate            # SQLite by default
```

### Build the content from the real PHP sources

```bash
# The manual (clones php/doc-en, ~11k pages — takes a few minutes)
php artisan docs:sync

# News + releases (clones php/web-php — sparse, fast)
php artisan web:sync
```

Or do everything in one shot, producing the deployable SQLite file:

```bash
php artisan docs:build         # migrate + fetch + import manual + sync site content + index
```

### Run it

```bash
composer run dev               # serve + queue + logs + Vite, all at once
# → http://localhost:8000
```

If a frontend change doesn't show up, run `npm run build` (or `npm run dev`).

### Browser search (InlaySQL vector + BM25)

Search runs **entirely in the browser** on [InlaySQL](https://github.com/inlaySQL/inlaysql),
a SQLite-shaped engine with native vector and BM25 retrieval. The manual is
baked into one index at build time and queried client-side — no search server,
no PHP database extension, no per-keystroke network round trip.

```bash
bash scripts/inlaysql/install-wasm.sh   # fetch the InlaySQL WASM engine
php artisan search:build --fresh        # export manual → build manual-search.inlay
```

`search:build` streams each page (slug, title, type, purpose and an excerpt)
through the WASM module's own `embed()` into a `pages` table with a BM25 index
over the text and an HNSW index over the vector, then fuses them:

```sql
SELECT slug, title,
       fuse(vector_score(embedding, ?1), bm25_score(body, ?2)) AS score
FROM pages ORDER BY score DESC LIMIT 8
```

Because the corpus and the query are embedded by the **same function** — Rust
hashed trigrams, run in Node at build time and in WASM at query time — the
vectors always line up. The full manual is ~48 MB uncompressed and **~5 MB
gzipped**.

At runtime `search:pull` installs the published artifact and
`/manual-search.inlay` streams it gzipped; the browser fetches it lazily the
first time the search box is focused and falls back to the server-side FTS5
search if the engine can't load.

### Ask AI (retrieval-augmented assistant)

The floating **Ask AI** widget answers questions from the manual. Retrieval
runs in the browser against the same InlaySQL index; the retrieved pages and the
question are handed to a Livewire component, which calls a model server-side and
renders a friendly answer with links to the pages it used:

```
browser: question ──► InlaySQL WASM hybrid search ──► top 6 pages
       └────────────► Livewire ask-ai ──► laravel/ai ──► OpenRouter (server-side key)
                                              └── answer + citation indices
       ◄──────────── answer (Markdown) + linked source pages
```

- The API key never reaches the client — the call is made by the server.
- **Three models fail over in order**: a model is abandoned if its first token
  takes longer than `first_token_timeout` (3s) or it errors, and a global
  `deadline` (45s) caps the whole chain — so a slow model can't produce a 504.
  The widget shows which model answered.
- Citations are **indices**, mapped back to our own page URLs; the model never
  authors links. HTML in the answer is stripped before rendering.
- It's a chat: the question appears instantly, a typing indicator runs, and the
  answer streams into a message bubble with its sources.
- Per-IP rate limits (`assistant.throttle`) and a max question length protect the
  free tier.
- If the browser can't load the index, the component falls back to server-side
  FTS5 retrieval.

It is **off unless `OPENROUTER_API_KEY` is set**. Configure it in `.env`:

```dotenv
OPENROUTER_API_KEY=sk-or-...
ASSISTANT_MODEL=nvidia/nemotron-3-super-120b-a12b:free
ASSISTANT_MODEL_FALLBACK_1=google/gemma-4-31b-it:free
ASSISTANT_MODEL_FALLBACK_2=nex-agi/nex-n2.5-mini:free
```

Any OpenRouter model ids work; the defaults are free ones. See
[`config/assistant.php`](config/assistant.php) for the retrieval count, excerpt
size, timeouts and limits.

### Keeping in sync with upstream

`docs:sync` and `web:sync` are **incremental** — unchanged files are skipped by
content hash, so re-running is cheap. Both are scheduled daily
(`routes/console.php`), so a deployment with the scheduler running self-updates
as `php/doc-en` and `php/web-php` change.

### Quality gates

```bash
composer test                  # Pest
composer analyse               # PHPStan, max level  (use --memory-limit=512M if it OOMs)
composer lint                  # Pint (apply)
```

---

## Deploy

The site runs as a single container. Because the manual ships as a prebuilt
SQLite file, **no database server is required** at runtime.

### Docker (simplest)

```bash
php artisan docs:build         # bake the populated SQLite artifact first
docker compose up --build      # → http://localhost:8088
```

`docker-compose.yml` maps host port **8088** to the container's **8080**
(nginx). It's a single multi-stage build: Node compiles the assets, then they're
copied into the `serversideup/php:8.4-fpm-nginx` image alongside the SQLite file.
Sessions use cookies and the cache uses files, so there are no external service
dependencies.

### Prebuilt content artifact (recommended)

Pushing a version tag rebuilds the SQLite manual from the latest upstream
sources and attaches it to the GitHub Release (see
[`.github/workflows/content-release.yml`](.github/workflows/content-release.yml)):

```bash
git tag v1.2.0 && git push origin v1.2.0
```

The release then carries the freshly rebuilt artifacts, produced from the latest
`php/doc-en` + `php/web-php`:

- `database.sqlite.gz` — the server-side manual.
- `manual-search.inlay.gz` — the browser search index.

**Download them and install** in one command each — no clone of the upstream
sources required:

```bash
php artisan docs:pull --force     # download → verify sha256 → replace database/database.sqlite
php artisan search:pull --force   # download → verify sha256 → install the browser search index
```

Prefer to do it by hand? Grab
[`database.sqlite.gz`](https://github.com/lam0819/php-rebrand/releases/latest/download/database.sqlite.gz)
from the latest release and run:

```bash
gunzip -c database.sqlite.gz > database/database.sqlite
```

`docs:pull` resolves the configured `DOCS_ARTIFACT_URL` (defaults to the "latest
release" asset), verifies the sha256 when available, and replaces
`database/database.sqlite` atomically (pass `--url=...` to pin a specific tag).

### Any PHP host / Laravel Cloud

It's a standard Laravel 12 app. As a **build command** install the WASM engine
and pull both artifacts so the latest prebuilt content ships with each
deployment, then serve with PHP-FPM + a web server:

```bash
bash scripts/inlaysql/install-wasm.sh
php artisan docs:pull --force
php artisan search:pull --force
```

Run the scheduler (`php artisan schedule:run` every minute, or `schedule:work`)
if you want the site to keep itself current with upstream automatically.

#### Auto-deploy from a version tag (deploy hook)

Laravel Cloud can deploy from a **deploy hook** URL instead of a push. It has no
CLI command — create it in the dashboard: **Application → Environment → Settings
→ Deployments → enable "Deploy hook" → copy the URL**. The URL is the only
credential, so store it as a secret.

1. In GitHub: **Settings → Secrets and variables → Actions → New repository
   secret**, named `LARAVEL_CLOUD_DEPLOY_HOOK`, with the URL as the value.
2. Set the environment's **build command** to the three lines above.
3. `git push origin v1.2.0` — the [release workflow](.github/workflows/content-release.yml)
   builds the new SQLite and search index, attaches them to the release, then
   `POST`s the hook so Laravel Cloud deploys with the fresh content.

Trigger it by hand any time (optionally pinning a commit on the environment's
branch):

```bash
curl -X POST "https://your-deploy-hook-url"
curl -X POST "https://your-deploy-hook-url?commit_hash=abc123def456"
```

---

## Contributing — pull requests only

**Issues are disabled on this repository. Every contribution happens through a
pull request.** Found a bug? Have an idea or a feature request? Don't open an
issue — fork the repo, make the change, and send a PR. PRs are the only channel:
a good PR with a clear description *is* the proposal, the discussion, and the
fix in one place.

See [`CONTRIBUTING.md`](CONTRIBUTING.md) for the full workflow. In short:

A good change keeps the project honest about its single source of truth —
content is parsed, never hand-written.

1. **Pick the right pipeline.** Manual rendering / parsing lives in `app/Docs`;
   news and release content lives in `app/Web`. Page markup lives in
   `resources/views`.
2. **Match the conventions.** PHP 8.4 strict types, constructor property
   promotion, explicit return types, small single-responsibility classes,
   dependency injection over facades in the core. Check sibling files first.
3. **Every change is tested.** Add or update a Pest test and run the focused
   suite (`php artisan test --compact --filter=...`).
4. **Green gates before a PR:** `composer test`, `composer analyse`, and
   `vendor/bin/pint --dirty`.
5. If you're adding a new document type, implement the `DocumentParser` contract
   and register it — discovery, loading, and persistence are untouched (see
   [`docs/Architecture.md`](docs/Architecture.md#adding-a-new-parser)).

Please **don't** commit hand-authored manual/news/release content — if something
renders wrong, fix the parser or renderer so every page benefits.

---

## Credits

- **Design** by [Open Design](https://open-design.ai) — the "PHP, evolved"
  design system, layouts, and tokens.
- **Built 100% by AI** — every line of application code was written by
  **Claude** (Anthropic) and **Codex** (OpenAI), under human direction.
- **Content** belongs to the PHP project and its contributors, sourced from
  [`php/doc-en`](https://github.com/php/doc-en) and
  [`php/web-php`](https://github.com/php/web-php). PHP, the PHP logo, and the
  ElePHPant are the property of their respective owners.

---

## License

Open source under the **[Apache License 2.0](LICENSE)**.

The application code in this repository is licensed under Apache 2.0. The PHP
manual content and news it imports remain under their original licenses from the
PHP project (the manual is licensed under the
[Creative Commons Attribution 3.0](https://creativecommons.org/licenses/by/3.0/)
license).
