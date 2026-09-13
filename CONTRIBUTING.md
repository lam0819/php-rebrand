# Contributing to PHP, evolved

Thanks for wanting to help. This project is open source and **everyone is
welcome to contribute**.

## Pull requests are the only way in

> **This repository does not accept issues.**
>
> GitHub Issues are intentionally disabled. Every bug report, feature request,
> question, or design idea must arrive as a **pull request**. A PR is not just
> code — it's the proposal, the discussion, and the fix, all in one place.

This keeps the project focused: if something is worth changing, it's worth a
patch. If you have an idea you can't fully implement yet, open a **draft PR**
with a clear description of what you're proposing and we'll iterate there.

## The golden rule

**Content is parsed, never hand-written.** The manual, news, and release data
come from the official PHP source repositories. If a page renders wrong, fix the
parser or the renderer so every page benefits — never patch the output.

| You want to change | Look in |
|---|---|
| Manual parsing / rendering | `app/Docs` |
| News & release syncing | `app/Web` |
| Page markup & styling | `resources/views`, `resources/css` |
| Import commands / pipeline | `app/Console/Commands` |
| Tests | `tests/` |
| Design & architecture notes | `docs/Architecture.md` |

## Workflow

1. **Fork** the repository and create a topic branch:
   `git checkout -b feat/short-description`.
2. **Match the conventions.** PHP 8.4 strict types, constructor property
   promotion, explicit return types, small single-responsibility classes,
   dependency injection over facades in the core. Read the sibling files first.
3. **Test every change.** Add or update a Pest test and run the focused suite:
   `php artisan test --compact --filter=...`.
4. **Run the gates** before pushing:
   ```bash
   composer test        # Pest
   composer analyse     # PHPStan (max level)
   vendor/bin/pint      # Pint, apply formatting
   ```
5. **Open a pull request** against `main` with:
   - what the change does and **why**,
   - how you verified it (tests or manual steps),
   - any screenshots for UI changes.

Draft PRs are welcome for early feedback — mark them "Draft" and iterate.

## What we'll merge

- Focused, well-tested changes that respect the single-source-of-truth design.
- Parser/renderer fixes that improve correctness for everyone.
- Performance, accessibility, and search improvements.
- Documentation and developer-experience improvements.

## What we won't merge

- Hand-authored manual, news, or release content (fix the parser instead).
- Changes that add a runtime database requirement — production ships as a
  single prebuilt SQLite artifact.
- Large refactors with no tests or clear motivation.

## Licensing

By submitting a pull request you agree that your contribution is licensed under
the same [Apache License 2.0](LICENSE) that covers this project. Imported PHP
manual content remains under its original
[CC BY 3.0](https://creativecommons.org/licenses/by/3.0/) license.
