import { test, expect } from '@playwright/test';

const ADMIN = {
    email: 'admin@sman11.sch.id',
    password: 'Admin.123',
};

const USER = {
    email: 'tester@example.com',
    password: 'Password123!',
};

async function login(page, creds) {
    await page.goto('/login');
    await expect(page.getByRole('heading', { name: /selamat datang/i })).toBeVisible();
    await page.locator('#email').fill(creds.email);
    await page.locator('#password').fill(creds.password);
    await page.getByRole('button', { name: /masuk/i }).click();
}

async function logout(page) {
    await page.getByRole('button', { name: /keluar|logout/i }).first().click();
    await page.getByRole('button', { name: /keluar|logout/i }).last().click();
    await expect(page).toHaveURL(/login/);
}

test('admin and user smoke flow', async ({ page }) => {
    // Admin flow
    await login(page, ADMIN);
    await expect(page.getByRole('heading', { name: /dashboard admin|admin dashboard/i })).toBeVisible();

    await page.getByRole('button', { name: /📋|status/i }).first().click();
    await page.getByRole('button', { name: /🕐|newest/i }).first().click();

    const manageLink = page.getByRole('link', { name: /kelola lengkap|manage full|kelola/i }).first();
    await expect(manageLink).toBeVisible();
    await manageLink.click();
    await expect(page).toHaveURL(/admin\/complaints/);

    const detailBtn = page.locator('button[title="Lihat Detail"], button:has-text("Lihat Detail")').first();
    if (await detailBtn.count()) {
        await detailBtn.click();
        await expect(page.getByRole('heading', { name: /complaint detail|detail/i })).toBeVisible();
        await page.locator('.modal-overlay').first().click({ position: { x: 8, y: 8 } });
    }

    const updateBtn = page.locator('button[title="Update Status"], button:has-text("Update Status")').first();
    if (await updateBtn.count()) {
        await updateBtn.click();
        await expect(page.getByRole('heading', { name: /update status/i })).toBeVisible();
        await page.locator('.modal-overlay').first().click({ position: { x: 8, y: 8 } });
    }

    await logout(page);

    // User flow
    await login(page, USER);
    await expect(page.getByRole('heading', { name: /^dashboard$/i })).toBeVisible();

    await page.getByRole('link', { name: /pengaduanku|riwayat pengaduan|my complaints|riwayat/i }).first().click();
    await expect(page).toHaveURL(/complaints/);

    const firstCard = page.locator('.glass-card').nth(1);
    if (await firstCard.count()) {
        await firstCard.click();
    }

    await page.getByRole('link', { name: /buat pengaduan|buat laporan|create complaint/i }).first().click();
    await expect(page.getByRole('heading', { name: /buat laporan baru|create complaint/i })).toBeVisible();
    await expect(page.getByRole('button', { name: /kirim|submit|pengaduan/i })).toBeDisabled();

    await logout(page);
});

