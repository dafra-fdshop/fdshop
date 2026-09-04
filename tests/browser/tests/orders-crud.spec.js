const { test, expect } = require('@playwright/test');
const { authenticateAdministrator, installDiagnostics, openView } = require('../support/browser');
test.setTimeout(120_000);
test.describe.configure({ mode: 'serial' });
test.beforeEach(async ({ page, context, baseURL }, testInfo) => {
  testInfo.diagnostics = await installDiagnostics(page, baseURL);
  await authenticateAdministrator(page, context, testInfo);
});
test.afterEach(async ({}, testInfo) => testInfo.diagnostics.expectClean());
async function clearFilters(page) {
  const clear = page.getByRole('button', { name: 'Clear' });
  if (await clear.isVisible() && await clear.isEnabled()) { await clear.click(); await page.waitForLoadState('networkidle'); }
}
async function search(page, value) {
  await openView(page, 'orders'); await clearFilters(page);
  await page.locator('#filter_search').fill(value); await page.locator('#filter_search').press('Enter'); await page.waitForLoadState('networkidle');
}
async function setStatus(page, statusId) {
  await search(page, 'E2E-ORDER-NORMAL');
  const row = page.locator('#orderList tbody tr').filter({ hasText: 'E2E-ORDER-NORMAL' });
  await row.getByRole('checkbox', { name: 'Select' }).check();
  await page.locator('#bulk_order_status_id').selectOption(statusId);
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.getByRole('button', { name: 'Save' }).click()]);
  await page.waitForLoadState('networkidle');
}
async function openNormal(page) { await search(page, 'E2E-ORDER-NORMAL'); await page.getByRole('link', { name: 'E2E-ORDER-NORMAL' }).click(); await page.waitForLoadState('networkidle'); }
test('order list, search, status filters and fixture snapshot details', async ({ page }) => {
  await openView(page, 'orders');
  await expect(page.locator('#orderList')).toContainText('E2E-ORDER-NORMAL');
  await expect(page.locator('#orderList')).toContainText('E2E-ORDER-BUNDLE');
  await search(page, 'E2E-ORDER-NORMAL');
  await expect(page.locator('#orderList tbody tr')).toHaveCount(1);
  await expect(page.locator('#orderList')).not.toContainText('E2E-ORDER-BUNDLE');
  await clearFilters(page);
  const options = page.getByRole('button', { name: 'Filter Options' }); if (await options.isVisible()) await options.click();
  await page.locator('#filter_status').selectOption('900700', { force: true }); await page.waitForLoadState('networkidle');
  await expect(page.locator('#orderList')).toContainText('E2E-ORDER-NORMAL'); await expect(page.locator('#orderList')).not.toContainText('E2E-ORDER-BUNDLE');
  await openNormal(page);
  const body = page.locator('main');
  await expect(body).toContainText('E2E-ORDER-NORMAL'); await expect(body).toContainText('E2E Bestellt');
  await expect(body).toContainText('E2E Produkt Aktiv'); await expect(body).toContainText('E2E-PROD-ACTIVE');
  await expect(body).toContainText('9900000000001'); await expect(body).toContainText('E2E Hersteller Aktiv');
  await expect(body).toContainText('19,99 EUR'); await expect(body).toContainText('MwSt.: 19,00 %');
  await expect(body).toContainText('E2E Snapshot angelegt'); await expect(body).toContainText('Künstlicher Ausgangsstatus');
  await search(page, 'E2E-ORDER-BUNDLE'); await page.getByRole('link', { name: 'E2E-ORDER-BUNDLE' }).click();
  await expect(page.locator('main')).toContainText('E2E-ORDER-BUNDLE'); await expect(page.locator('main')).toContainText('E2E Produkt Aktionspreis');
  await expect(page.locator('main')).toContainText('66,48'); await expect(page.locator('main')).toContainText('E2E Bundle-Snapshot angelegt');
});
test('order status and item mutations preserve snapshots and histories', async ({ page }) => {
  await setStatus(page, '900701'); await openNormal(page);
  await expect(page.locator('main')).toContainText('E2E Versendet'); await expect(page.locator('main')).toContainText('E2E Bestellt → E2E Versendet');
  await expect(page.locator('main')).toContainText('E2E-PROD-ACTIVE'); await expect(page.locator('main')).toContainText('9900000000001');
  await setStatus(page, '900700'); await openNormal(page);
  await expect(page.locator('main')).toContainText('E2E Versendet → E2E Bestellt');
  await page.locator('#jform_product_id').selectOption('900105'); await page.locator('#jform_quantity').fill('2');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.getByRole('button', { name: 'Hinzufügen' }).click()]);
  const itemRows = page.getByText('Bestellpositionen', { exact: true }).locator('..').locator('tbody tr');
  let added = itemRows.filter({ hasText: 'E2E-PROD-DISCOUNT' }); await expect(added).toHaveCount(1); await expect(page.locator('main')).toContainText('99,97');
  await added.locator('input[name="quantity"]').fill('3');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), added.getByRole('button', { name: 'Speichern' }).click()]);
  added = itemRows.filter({ hasText: 'E2E-PROD-DISCOUNT' }); await expect(added.locator('input[name="quantity"]')).toHaveValue('3'); await expect(page.locator('main')).toContainText('139,96');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), added.getByRole('button', { name: 'Entfernen' }).click()]);
  added = itemRows.filter({ hasText: 'E2E-PROD-DISCOUNT' }); await expect(added).toContainText('Entfernt'); await expect(page.locator('main')).toContainText('19,99');
  await expect(page.locator('main')).toContainText('Produkt hinzugefügt'); await expect(page.locator('main')).toContainText('Menge geändert'); await expect(page.locator('main')).toContainText('Produkt entfernt');
  const original = page.locator('table tbody tr').filter({ hasText: 'E2E-PROD-ACTIVE' }); await expect(original).toContainText('9900000000001'); await expect(original).not.toContainText('Entfernt');
});
