import { defineConfig } from '@playwright/test';

const PORT = 8123;
const BASE_URL = `http://127.0.0.1:${PORT}`;

// Live end-to-end tests: Playwright drives a real browser against the served
// workbench (composer serve). It boots the Filament panels with the Okta plugin,
// so the scenarios cover the login screen and the start of the OIDC redirect.
export default defineConfig({
    testDir: './tests/e2e',
    timeout: 30_000,
    expect: { timeout: 10_000 },
    fullyParallel: true,
    reporter: 'list',
    use: {
        baseURL: BASE_URL,
        headless: true,
        trace: 'on-first-retry',
    },
    webServer: {
        command: `vendor/bin/testbench workbench:build --ansi && vendor/bin/testbench filament:assets --ansi && APP_URL=${BASE_URL} vendor/bin/testbench serve --port=${PORT}`,
        url: `${BASE_URL}/admin/login`,
        reuseExistingServer: !process.env.CI,
        timeout: 180_000,
    },
});
