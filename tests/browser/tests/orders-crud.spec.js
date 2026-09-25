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
async function openNormal(page) { await search(page, 'E2E-ORDER-NORMAL'); await page.getByRole('link', { name: 'E2E-ORDER-NORMAL' }).click(); await page.waitForLoadState('networkidle'); }
async function expectProductStatus(page, sku, status) {
  await openView(page, 'products'); await clearFilters(page);
  await page.locator('#filter_search').fill(sku); await page.locator('#filter_search').press('Enter'); await page.waitForLoadState('networkidle');
  await expect(page.locator('#productList tbody tr').filter({ hasText: sku })).toContainText(status);
}
async function changeNormalOrderStatus(page, statusId) {
  await search(page, 'E2E-ORDER-NORMAL');
  await page.locator('#orderList tbody tr').filter({ hasText: 'E2E-ORDER-NORMAL' }).locator('input[name="cid[]"]').check();
  await page.locator('#bulk_order_status_id').selectOption(statusId);
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.getByRole('button', { name: /Save|Speichern/i }).click()]);
  await expect(page.locator('#system-message-container')).toContainText('1 Bestellung(en) aktualisiert');
}
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
  await expect(body).toContainText('Erika Mustermann'); await expect(body).toContainText('E2E Handel GmbH');
  await expect(body).toContainText('Teststraße 12'); await expect(body).toContainText('12345 Teststadt');
  await expect(body).toContainText('Deutschland'); await expect(body).toContainText('+49 30 123456');
  await expect(body).toContainText('E2E Snapshot angelegt'); await expect(body).toContainText('Künstlicher Ausgangsstatus');
  await search(page, 'E2E-ORDER-BUNDLE'); await page.getByRole('link', { name: 'E2E-ORDER-BUNDLE' }).click();
  await expect(page.locator('main')).toContainText('E2E-ORDER-BUNDLE'); await expect(page.locator('main')).toContainText('E2E Produkt Aktionspreis');
  await expect(page.locator('main')).toContainText('66,48'); await expect(page.locator('main')).toContainText('E2E Bundle-Snapshot angelegt');
  const getSaveResponse = await page.evaluate(async () => {
    const response = await fetch('/administrator/index.php?option=com_fdshop&task=order.save&id=900801', { credentials: 'same-origin' });
    return response.text();
  });
  expect(getSaveResponse).toContain('ausschließlich per POST zulässig');
});
test('reserved order edits stay draft-only until atomic toolbar save', async ({ page }) => {
  await openNormal(page);
  const rows = page.locator('[data-order-items] tbody tr');
  let original = rows.filter({ hasText: 'E2E-PROD-ACTIVE' });
  await expect(page.getByRole('button', { name: /Save|Speichern/i })).toBeVisible();
  await expect(page.getByRole('button', { name: /Close|Cancel|Abbrechen/i })).toBeVisible();
  await original.locator('input[type="number"]').fill('2');
  await page.locator('#jform_product_id').selectOption('900105'); await page.locator('#jform_quantity').fill('2');
  await page.getByRole('button', { name: 'Hinzufügen' }).click();
  await expect(page.locator('[data-new-items]')).toContainText('E2E Produkt Aktionspreis');
  await page.locator('#jform_shipment_id').selectOption('900602');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.getByRole('button', { name: /Close|Cancel|Abbrechen/i }).click()]);
  await openNormal(page); original = page.locator('[data-order-items] tbody tr').filter({ hasText: 'E2E-PROD-ACTIVE' });
  await expect(original.locator('input[type="number"]')).toHaveValue('1'); await expect(page.locator('[data-new-items]')).toBeEmpty(); await expect(page.locator('#jform_shipment_id')).toHaveValue('900600');

  await original.locator('input[type="number"]').fill('999999');
  await page.locator('#jform_product_id').selectOption('900105'); await page.locator('#jform_quantity').fill('2'); await page.getByRole('button', { name: 'Hinzufügen' }).click();
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.getByRole('button', { name: /Save|Speichern/i }).click()]);
  await expect(page.locator('#system-message-container')).toContainText('Bestand reicht');
  original = page.locator('[data-order-items] tbody tr').filter({ hasText: 'E2E-PROD-ACTIVE' }); await expect(original.locator('input[type="number"]')).toHaveValue('1');

  await original.locator('input[type="number"]').fill('2'); await page.locator('#jform_product_id').selectOption('900105'); await page.locator('#jform_quantity').fill('2'); await page.getByRole('button', { name: 'Hinzufügen' }).click(); await page.locator('#jform_shipment_id').selectOption('900602');
  const staleRevision = await page.locator('input[name="expected_modified"]').inputValue();
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.getByRole('button', { name: /Save|Speichern/i }).click()]);
  await expect(page.locator('#system-message-container')).toContainText('atomar gespeichert'); await expect(page.locator('main')).toContainText('Bestellung geändert');
  original = page.locator('[data-order-items] tbody tr').filter({ hasText: 'E2E-PROD-ACTIVE' }); await expect(original.locator('input[type="number"]')).toHaveValue('2');
  let added = page.locator('[data-order-items] tbody tr').filter({ hasText: 'E2E-PROD-DISCOUNT' }); await expect(added).toHaveCount(1); await expect(added).toContainText('39,99 EUR'); await expect(page.locator('#jform_shipment_id')).toHaveValue('900602');
  await expectProductStatus(page, 'E2E-PROD-ACTIVE', 'wenige Verfügbar'); await openNormal(page);

  const staleResponse = await page.evaluate(async revision => {
    const form = document.querySelector('#adminForm'); const body = new FormData(form);
    body.set('expected_modified', revision); body.set('items[900810][quantity]', '3');
    const response = await fetch(form.action, { method: 'POST', body, credentials: 'same-origin' }); return response.text();
  }, staleRevision);
  expect(staleResponse).toContain('zwischenzeitlich geändert'); await page.reload({ waitUntil: 'networkidle' }); original = page.locator('[data-order-items] tbody tr').filter({ hasText: 'E2E-PROD-ACTIVE' }); await expect(original.locator('input[type="number"]')).toHaveValue('2');

  await original.locator('input[type="number"]').fill('1'); await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.getByRole('button', { name: /Save|Speichern/i }).click()]);
  await expectProductStatus(page, 'E2E-PROD-ACTIVE', 'Verfügbar'); await openNormal(page);
  added = page.locator('[data-order-items] tbody tr').filter({ hasText: 'E2E-PROD-DISCOUNT' }); await added.getByRole('button', { name: 'Entfernen' }).click(); await expect(added).toHaveClass(/table-danger/);
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.getByRole('button', { name: /Save|Speichern/i }).click()]);
  await expect(page.locator('[data-order-items] tbody tr').filter({ hasText: 'E2E-PROD-DISCOUNT' })).toContainText('Entfernt');
  await expectProductStatus(page, 'E2E-PROD-DISCOUNT', 'Verfügbar');
});
test('stock transitions recalculate affected persisted product status', async ({ page }) => {
  await changeNormalOrderStatus(page, '900704');
  await expectProductStatus(page, 'E2E-PROD-ACTIVE', 'Verfügbar');
  await changeNormalOrderStatus(page, '900700');
  await expectProductStatus(page, 'E2E-PROD-ACTIVE', 'Verfügbar');
  await changeNormalOrderStatus(page, '900701');
  await expectProductStatus(page, 'E2E-PROD-ACTIVE', 'Verfügbar');
});
test('deducted bundle order permits shipment-only save but blocks content changes', async ({ page }) => {
  await search(page, 'E2E-ORDER-BUNDLE'); await page.getByRole('link', { name: 'E2E-ORDER-BUNDLE' }).click(); await page.waitForLoadState('networkidle');
  await expect(page.locator('main')).toContainText('E2E-ORDER-BUNDLE'); await page.locator('#jform_shipment_id').selectOption('900602');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.getByRole('button', { name: /Save|Speichern/i }).click()]);
  await expect(page.locator('#system-message-container')).toContainText('atomar gespeichert'); await expect(page.locator('main')).toContainText('Käufer-E-Mail-Adresse ist ungültig');
  const normal = page.locator('[data-order-items] tbody tr').filter({ hasText: 'E2E-PROD-DISCOUNT' }); await normal.locator('input[type="number"]').fill('2');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.getByRole('button', { name: /Save|Speichern/i }).click()]);
  await expect(page.locator('#system-message-container')).toContainText('endgültig abgezogene Bestände'); await expect(page.locator('#jform_shipment_id')).toHaveValue('900602'); await expect(page.locator('[data-order-items] tbody tr').filter({ hasText: 'E2E-PROD-DISCOUNT' }).locator('input[type="number"]')).toHaveValue('1'); await expect(page.locator('main')).toContainText('E2E-ORDER-BUNDLE');
});
