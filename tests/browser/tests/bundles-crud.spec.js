const { test, expect } = require('@playwright/test');
const { authenticateAdministrator, installDiagnostics, openView } = require('../support/browser');

const bundleName = 'E2E-CRUD-BUNDLE-REFERENCE';

test.setTimeout(120_000);
test.describe.configure({ mode: 'serial' });

test.beforeEach(async ({ page, context, baseURL }, testInfo) => {
  testInfo.diagnostics = await installDiagnostics(page, baseURL);
  await authenticateAdministrator(page, context, testInfo);
});

test.afterEach(async ({}, testInfo) => testInfo.diagnostics.expectClean());

async function clearFilters(page) {
  const clear = page.getByRole('button', { name: 'Clear' });
  if (await clear.isVisible() && await clear.isEnabled()) {
    await clear.click();
    await page.waitForLoadState('networkidle');
  }
}

async function searchBundle(page, value, published = '') {
  await openView(page, 'bundles');
  await clearFilters(page);
  const options = page.getByRole('button', { name: 'Filter Options' });
  if (await options.isVisible()) await options.click();
  await page.locator('#filter_published').selectOption(published, { force: true });
  await page.waitForLoadState('networkidle');
  await page.locator('#filter_search').fill(value);
  await page.locator('#filter_search').press('Enter');
  await page.waitForLoadState('networkidle');
}

function matchingRow(page) {
  return page.locator('#bundleList tbody tr').filter({ hasText: bundleName });
}

async function addProductBySuggestion(page, sku, productName) {
  const searchResponse = page.waitForResponse(response =>
    response.url().includes('task=bundle.searchProducts') && response.url().includes(`q=${encodeURIComponent(sku)}`)
  );
  await page.locator('#bundle-product-sku').fill(sku);
  expect((await searchResponse).ok()).toBe(true);
  const suggestion = page.locator('#bundle-product-suggestions [role="option"]').filter({ hasText: productName });
  await expect(suggestion).toBeVisible();
  await suggestion.click();
  const lookupResponse = page.waitForResponse(response =>
    response.url().includes('task=bundle.lookupProduct') && response.url().includes(`sku=${encodeURIComponent(sku)}`)
  );
  await page.locator('#bundle-product-add').click();
  expect((await lookupResponse).ok()).toBe(true);
  await expect(page.locator(`#bundle-product-table tr[data-product-id]`).filter({ hasText: sku })).toHaveCount(1);
}

async function acceptDelete(page, action) {
  await action();
  const confirmation = page.getByRole('dialog', { name: 'Warning' });
  await expect(confirmation).toBeVisible();
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }),
    confirmation.getByRole('button', { name: 'Yes', exact: true }).click(),
  ]);
  await page.waitForLoadState('networkidle');
}

test('bundle invalid save, automatic number, AJAX products, discounts, apply, save-close and status', async ({ page }) => {
  const assetResponses = [];
  page.on('response', response => {
    if (response.url().includes('/media/com_fdshop/js/admin-bundle.js')) assetResponses.push(response);
  });

  await openView(page, 'bundles');
  await page.getByRole('button', { name: 'New' }).click();
  await expect(page).toHaveURL(/view=bundle.*layout=edit/);
  await expect(page.locator('#jform_bundle_number')).toHaveValue('');
  await page.getByRole('button', { name: 'Save & Close' }).click();
  await expect(page).toHaveURL(/view=bundle.*layout=edit/);
  await expect(page.locator('#jform_bundle_name')).toHaveJSProperty('validity.valid', false);
  await searchBundle(page, bundleName);
  await expect(matchingRow(page)).toHaveCount(0);

  assetResponses.length = 0;
  await openView(page, 'bundle&layout=edit');
  await expect.poll(() => assetResponses.length).toBeGreaterThan(0);
  expect(assetResponses.length).toBe(1);
  expect(assetResponses[0].ok()).toBe(true);
  await page.locator('#jform_bundle_name').fill(bundleName);
  await page.locator('#jform_alias').fill('e2e-crud-bundle-reference');
  await page.locator('#jform_description').fill('CRUD initial bundle description');
  await page.locator('input[name="jform[is_active]"][value="1"]').check({ force: true });

  await page.getByRole('tab', { name: 'Produkte' }).click();
  await addProductBySuggestion(page, 'E2E-PROD-ACTIVE', 'E2E Produkt Aktiv');
  await addProductBySuggestion(page, 'E2E-PROD-DISCOUNT', 'E2E Produkt Aktionspreis');
  await addProductBySuggestion(page, 'E2E-PROD-ACTIVE', 'E2E Produkt Aktiv');
  await expect(page.locator('#bundle-product-table tbody tr')).toHaveCount(2);

  await page.getByRole('tab', { name: 'Rabattstufen' }).click();
  let rules = page.locator('#bundle-discount-table tbody tr');
  await rules.nth(0).locator('input[name*="[min_quantity]"]').fill('2');
  await rules.nth(0).locator('input[name*="[discount_percent]"]').fill('5');
  await page.locator('#bundle-discount-add').click();
  rules = page.locator('#bundle-discount-table tbody tr');
  await rules.nth(1).locator('input[name*="[min_quantity]"]').fill('4');
  await rules.nth(1).locator('input[name*="[discount_percent]"]').fill('10');
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(/view=bundle.*layout=edit.*id=\d+/);
  const createdId = await page.locator('#jform_id').inputValue();
  const bundleNumber = await page.locator('#jform_bundle_number').inputValue();
  expect(Number(createdId)).toBeGreaterThan(0);
  expect(bundleNumber).toMatch(/^BUN-\d+$/);
  await page.getByRole('tab', { name: 'Produkte' }).click();
  await expect(page.locator('#bundle-product-table tbody tr')).toHaveCount(2);
  await page.getByRole('tab', { name: 'Rabattstufen' }).click();
  await expect(page.locator('#bundle-discount-table tbody tr')).toHaveCount(2);

  rules = page.locator('#bundle-discount-table tbody tr');
  await rules.nth(1).locator('input[name*="[discount_percent]"]').fill('12.5');
  await page.getByRole('tab', { name: 'Grunddaten' }).click();
  await page.locator('#jform_description').fill('CRUD bundle after apply');
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(new RegExp(`view=bundle.*layout=edit.*id=${createdId}`));
  await expect(page.locator('#jform_id')).toHaveValue(createdId);
  await expect(page.locator('#jform_bundle_number')).toHaveValue(bundleNumber);
  await page.getByRole('tab', { name: 'Rabattstufen' }).click();
  await expect(page.locator('#bundle-discount-table tbody tr')).toHaveCount(2);
  await expect(page.locator('#bundle-discount-table tbody tr').nth(1).locator('input[name*="[discount_percent]"]')).toHaveValue('12.5000');

  await page.locator('#bundle-discount-table tbody tr').nth(0).locator('.bundle-discount-remove').click();
  await expect(page.locator('#bundle-discount-table tbody tr')).toHaveCount(1);
  await page.getByRole('tab', { name: 'Produkte' }).click();
  await page.locator('#bundle-product-table tbody tr').filter({ hasText: 'E2E-PROD-DISCOUNT' }).locator('.bundle-product-remove').click();
  await expect(page.locator('#bundle-product-table tbody tr')).toHaveCount(1);
  await page.getByRole('tab', { name: 'Grunddaten' }).click();
  await page.locator('#jform_bundle_name').fill('E2E-CRUD-BUNDLE-UPDATED');
  await page.getByRole('button', { name: 'Save & Close' }).click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(/view=bundles/);
  await searchBundle(page, bundleNumber);
  await page.getByRole('link', { name: bundleNumber, exact: true }).click();
  await expect(page.locator('#jform_id')).toHaveValue(createdId);
  await expect(page.locator('#jform_bundle_number')).toHaveValue(bundleNumber);
  await expect(page.locator('#jform_bundle_name')).toHaveValue('E2E-CRUD-BUNDLE-UPDATED');
  await page.getByRole('tab', { name: 'Produkte' }).click();
  await expect(page.locator('#bundle-product-table tbody tr')).toHaveCount(1);
  await expect(page.locator('#bundle-product-table')).toContainText('E2E-PROD-ACTIVE');
  await expect(page.locator('#bundle-product-table')).not.toContainText('E2E-PROD-DISCOUNT');
  await page.getByRole('tab', { name: 'Rabattstufen' }).click();
  await expect(page.locator('#bundle-discount-table tbody tr')).toHaveCount(1);
  await expect(page.locator('#bundle-discount-table tbody tr input[name*="[min_quantity]"]')).toHaveValue('4.000');
  await expect(page.locator('#bundle-discount-table tbody tr input[name*="[discount_percent]"]')).toHaveValue('12.5000');

  await searchBundle(page, bundleNumber);
  let row = page.locator('#bundleList tbody tr').filter({ hasText: bundleNumber });
  await row.getByRole('link', { name: 'Unpublish Item' }).click();
  await searchBundle(page, bundleNumber, '0');
  row = page.locator('#bundleList tbody tr').filter({ hasText: bundleNumber });
  await expect(row.getByRole('link', { name: 'Publish Item' })).toBeVisible();
  await row.getByRole('link', { name: 'Publish Item' }).click();
  await searchBundle(page, bundleNumber, '1');
  await expect(page.locator('#bundleList tbody tr').filter({ hasText: bundleNumber }).getByRole('link', { name: 'Unpublish Item' })).toBeVisible();
  await searchBundle(page, bundleNumber, '');
  await expect(page.locator('#bundleList tbody tr').filter({ hasText: bundleNumber })).toHaveCount(1);

  await openView(page, 'bundles');
  await page.getByRole('button', { name: 'New' }).click();
  await page.locator('#jform_bundle_name').fill('E2E-CRUD-BUNDLE-MULTI');
  await page.locator('#jform_alias').fill('e2e-crud-bundle-multi');
  await page.locator('input[name="jform[is_active]"][value="1"]').check({ force: true });
  await page.getByRole('tab', { name: 'Produkte' }).click();
  await addProductBySuggestion(page, 'E2E-PROD-ACTIVE', 'E2E Produkt Aktiv');
  await page.getByRole('tab', { name: 'Rabattstufen' }).click();
  rules = page.locator('#bundle-discount-table tbody tr');
  await rules.nth(0).locator('input[name*="[min_quantity]"]').fill('3');
  await rules.nth(0).locator('input[name*="[discount_percent]"]').fill('7.5');
  await page.getByRole('button', { name: 'Save & Close' }).click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(/view=bundles/);
});

test('bundle direct delete removes multiple selected records through the UI', async ({ page }) => {
  await searchBundle(page, 'E2E-CRUD-BUNDLE');
  const primaryRow = page.locator('#bundleList tbody tr').filter({ hasText: 'E2E-CRUD-BUNDLE-UPDATED' });
  const multiRow = page.locator('#bundleList tbody tr').filter({ hasText: 'E2E-CRUD-BUNDLE-MULTI' });
  await expect(primaryRow).toHaveCount(1);
  await expect(multiRow).toHaveCount(1);
  await primaryRow.getByRole('checkbox', { name: 'Select' }).check();
  await multiRow.getByRole('checkbox', { name: 'Select' }).check();
  await acceptDelete(page, () => page.getByRole('button', { name: 'Delete' }).click());
  await searchBundle(page, 'E2E-CRUD-BUNDLE');
  await expect(primaryRow).toHaveCount(0);
  await expect(multiRow).toHaveCount(0);
});
