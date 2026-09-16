const { test, expect } = require('@playwright/test');
const { installDiagnostics } = require('../support/browser');

test.describe.configure({ mode: 'serial' });

test('filter panel exposes exactly the five V1 system filters', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.setViewportSize({ width: 1400, height: 1000 });
  await page.goto('/batterien');
  const panel = page.locator('.fdshop-category__filters .fdshop-filter');
  await expect(panel).toBeVisible();
  await expect(panel.locator('summary')).toHaveText(['Hersteller', 'Verfügbarkeit', 'Brenndauer', 'Kaliber', 'NEM']);
  await expect(panel).not.toContainText('Preisspanne');
  await expect(panel).not.toContainText('Abschuss');
  diagnostics.expectClean();
});

test('direct filter URL is server-rendered and validated', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  const response = await page.goto('/batterien?fd_filter%5Bavailability%5D%5B%5D=available');
  expect(response?.status()).toBe(200);
  await expect(page.locator('.fdshop-card')).toHaveCount(15);
  await expect(page.locator('.fdshop-filter-chip')).toContainText(['Auf Lager']);
  await page.goto('/batterien?fd_filter%5Bavailability%5D%5B%5D=manipulated&fd_filter%5Bnem%5D%5B%5D=999999');
  await expect(page.locator('.fdshop-card')).toHaveCount(24);
  await expect(page.locator('.fdshop-filter-chip')).toHaveCount(0);
  diagnostics.expectClean();
});

test('AJAX range filtering updates cards, URL, counts, chips and reset', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.setViewportSize({ width: 1400, height: 1000 });
  await page.goto('/batterien');
  const panel = page.locator('.fdshop-category__filters .fdshop-filter');
  await panel.getByText('20 bis 40 s', { exact: true }).click();
  await expect(page.locator('.fdshop-card')).toHaveCount(1);
  await expect(page.locator('.fdshop-card')).toHaveAttribute('data-product-id', '900100');
  await expect(page).toHaveURL(/fd_filter%5Bduration%5D%5B%5D=/);
  await expect(page.locator('.fdshop-filter-chip')).toContainText(['20 bis 40 s']);
  await page.locator('.fdshop-category__filters [data-fdshop-filter-reset]').click();
  await expect(page.locator('.fdshop-card')).toHaveCount(24);
  await expect(page.locator('.fdshop-filter-chip')).toHaveCount(0);
  await expect(page).not.toHaveURL(/fd_filter/);
  diagnostics.expectClean();
});

test('mobile offcanvas stays open while a filter is applied', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.setViewportSize({ width: 480, height: 900 });
  await page.goto('/batterien');
  await page.getByRole('button', { name: 'Produkte filtern' }).click();
  const offcanvas = page.locator('#fdshop-filter-offcanvas');
  await expect(offcanvas).toBeVisible();
  await offcanvas.getByText('20 bis 40 s', { exact: true }).click();
  await expect(page.locator('.fdshop-card')).toHaveCount(1);
  await expect(offcanvas).toBeVisible();
  diagnostics.expectClean();
});
