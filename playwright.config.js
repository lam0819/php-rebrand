import { defineConfig, devices } from '@playwright/test';

const port = process.env.E2E_PORT ?? 8123;
// Target an already-running site with E2E_BASE_URL=https://... (skips the local server).
const externalBaseURL = process.env.E2E_BASE_URL;
const baseURL = externalBaseURL ?? `http://127.0.0.1:${port}`;

const webServer = externalBaseURL
  ? undefined
  : {
      command: `php artisan serve --host=127.0.0.1 --port=${port}`,
      url: baseURL,
      reuseExistingServer: !process.env.CI,
      timeout: 120_000,
    };

export default defineConfig({
  testDir: './tests/e2e',
  timeout: 60_000,
  expect: { timeout: 20_000 },
  fullyParallel: false,
  workers: 1,
  reporter: process.env.CI ? 'github' : 'list',
  use: {
    baseURL,
    trace: 'on-first-retry',
  },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
  webServer,
});
