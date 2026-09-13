import { expect, test } from '@playwright/test';

/**
 * Smoke test for the in-browser InlaySQL search: the WASM engine and the
 * prebuilt index load lazily, the input is taken over, and hybrid retrieval
 * ranks the matching manual page first.
 *
 * Requires the built search index (storage/app/manual-search.inlay) and the
 * InlaySQL WASM bundle (public/inlaysql) — see README ("Browser search").
 */
test('browser search returns ranked manual pages', async ({ page }) => {
  await page.goto('/');

  const root = page.locator('[data-search-root]');
  const input = root.locator('[data-search]');
  await input.click();
  await input.fill('str_replace');

  const results = root.locator('[data-search-wasm-results]');
  await expect(results).toBeVisible({ timeout: 45_000 });

  const first = results.locator('.sr-item').first();
  await expect(first).toContainText('str_replace');
  await expect(first).toHaveAttribute('href', /\/manual\/reference-strings-functions-str-replace/);
});

test('browser search groups results by category', async ({ page }) => {
  await page.goto('/');

  const root = page.locator('[data-search-root]');
  await root.locator('[data-search]').click();
  // "install" spans manual guides and function pages.
  await root.locator('[data-search]').fill('install');

  const results = root.locator('[data-search-wasm-results]');
  await expect(results).toBeVisible({ timeout: 45_000 });

  const labels = results.locator('.sr-group-label');
  const names = await labels.allTextContents();
  expect(names.length).toBeGreaterThan(1);
  expect(names).toContain('Guide');
});

test('browser search resolves a function name typed before the engine loads', async ({ page }) => {
  await page.goto('/');

  const root = page.locator('[data-search-root]');

  // Type immediately, then let the engine finish loading and answer for the
  // value already in the box.
  await root.locator('[data-search]').fill('password_hash');

  const results = root.locator('[data-search-wasm-results]');
  await expect(results).toBeVisible({ timeout: 45_000 });
  // The exact page should be among the ranked results (ranking can vary).
  await expect(
    results.locator('a[href="/manual/reference-password-functions-password-hash"]'),
  ).toHaveCount(1);
});
