import fs from 'node:fs';
import path from 'node:path';
import { chromium } from 'playwright';

const baseUrl = 'http://pengaduan-sman11.test';
const outDir = path.resolve('tests/e2e/videos');
if (!fs.existsSync(outDir)) fs.mkdirSync(outDir, { recursive: true });

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
    baseURL: baseUrl,
    viewport: { width: 1440, height: 900 },
    recordVideo: { dir: outDir, size: { width: 1280, height: 720 } },
});
const page = await context.newPage();

async function login(email, password) {
    await page.goto('/login');
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);
    await page.getByRole('button', { name: /masuk/i }).click();
    await page.waitForTimeout(900);
}

async function logout() {
    await page.getByRole('button', { name: /keluar|logout/i }).first().click();
    await page.getByRole('button', { name: /keluar|logout/i }).last().click();
    await page.waitForURL(/login/);
}

// Admin flow
await login('admin@sman11.sch.id', 'Admin.123');
await page.getByRole('link', { name: /kelola lengkap|riwayat pengaduan|kelola/i }).first().click();
await page.waitForTimeout(1000);

const detailBtn = page.locator('button[title="Lihat Detail"]').first();
if (await detailBtn.count()) {
    await detailBtn.click();
    await page.waitForTimeout(900);
    await page.locator('.modal-overlay').first().click({ position: { x: 8, y: 8 } });
}
await page.waitForTimeout(700);
await logout();

// User flow
await login('tester@example.com', 'Password123!');
await page.getByRole('link', { name: /pengaduanku|riwayat/i }).first().click();
await page.waitForTimeout(1000);

const firstCard = page.locator('.glass-card').nth(1);
if (await firstCard.count()) {
    await firstCard.click();
    await page.waitForTimeout(800);
}

await page.getByRole('link', { name: /buat pengaduan|buat laporan/i }).first().click();
await page.waitForTimeout(1000);
await logout();

const video = page.video();
await context.close();
await browser.close();

if (video) {
    const originalPath = await video.path();
    const targetPath = path.resolve(outDir, 'smoke-preview.webm');
    if (fs.existsSync(targetPath)) fs.rmSync(targetPath);
    fs.copyFileSync(originalPath, targetPath);
    console.log(`Video saved to: ${targetPath}`);
}

