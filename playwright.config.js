import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 120000,
    expect: { timeout: 10000 },
    reporter: [['list'], ['html', { outputFolder: 'playwright-report', open: 'never' }]],
    use: {
        baseURL: 'http://pengaduan-sman11.test',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'on',
    },
});

