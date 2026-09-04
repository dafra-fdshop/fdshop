const { test, expect } = require('@playwright/test');
const { authenticateAdministrator, installDiagnostics, openView } = require('../support/browser');

test.beforeEach(async ({ page, context, baseURL }, testInfo) => {
  testInfo.diagnostics = await installDiagnostics(page, baseURL);
  await authenticateAdministrator(page, context, testInfo);
});

test.afterEach(async ({}, testInfo) => {
  testInfo.diagnostics.expectClean();
});

async function search(page, value, selector = '#filter_search') {
  const field = page.locator(selector);
  await expect(field).toBeVisible();
  await field.fill(value);
  await field.press('Enter');
  await page.waitForLoadState('networkidle');
}

async function clearFilters(page) {
  const clearButton = page.getByRole('button', { name: 'Clear' });
  if (await clearButton.isVisible() && await clearButton.isEnabled()) {
    await clearButton.click();
    await page.waitForLoadState('networkidle');
  }
}

async function selectFilter(page, selector, value) {
  await clearFilters(page);
  const optionsButton = page.getByRole('button', { name: 'Filter Options' });
  if (await optionsButton.isVisible()) {
    await optionsButton.click();
  }
  await page.locator(selector).selectOption(value, { force: true });
  await page.waitForLoadState('networkidle');
}

test('Dashboard: central structure is reachable', async ({ page }) => {
  await openView(page, 'dashboard');
  await expect(page.locator('main')).toContainText('FDShop läuft');
  if (process.env.FDSHOP_READONLY_INJECT_FAILURE === 'wrong-dashboard-title') {
    await expect(page.getByText('FDShop absichtlich falscher Titel', { exact: true })).toBeVisible();
  }
});

test('Products: fixtures, filters and forms are readable', async ({ page }) => {
  await openView(page, 'products');
  await selectFilter(page, '#filter_published', '*');
  await expect(page.locator('#productList')).toContainText('E2E-PROD-ACTIVE');
  await expect(page.locator('#productList')).toContainText('E2E-PROD-INACTIVE');
  await expect(page.locator('#productList')).not.toContainText('E2E-PROD-DELETED');
  await selectFilter(page, '#filter_published', '1');
  await expect(page.locator('#productList')).toContainText('E2E-PROD-ACTIVE');
  await expect(page.locator('#productList')).not.toContainText('E2E-PROD-INACTIVE');
  await search(page, 'E2E-PROD-ACTIVE');
  await expect(page.locator('#productList')).toContainText('E2E Produkt Aktiv');
  await openView(page, 'products');
  await selectFilter(page, '#filter_published', '0');
  await expect(page.locator('#productList')).toContainText('E2E-PROD-INACTIVE');
  await expect(page.locator('#productList')).not.toContainText('E2E-PROD-ACTIVE');
  await openView(page, 'products');
  await selectFilter(page, '#filter_deleted', '1');
  await expect(page.locator('#productList')).toContainText('E2E-PROD-DELETED');
  await expect(page.locator('#productList')).not.toContainText('E2E-PROD-ACTIVE');
  await expect(page.locator('#productList')).not.toContainText('E2E-PROD-INACTIVE');
  await openView(page, 'products');
  await clearFilters(page);
  await search(page, 'E2E-PROD-IMAGE');
  await page.getByRole('link', { name: 'E2E Produkt Bild', exact: true }).click();
  await expect(page.locator('#jform_product_name')).toHaveValue('E2E Produkt Bild');
  await expect(page.locator('#jform_sku')).toHaveValue('E2E-PROD-IMAGE');
  await page.getByRole('tab', { name: 'Medien' }).click();
  await expect(page.getByText('Aktuelles Bild', { exact: true })).toBeVisible();
  await expect(page.locator('img[src*="e2e-fixture-product.svg"]')).toBeVisible();
  await openView(page, 'products');
  await clearFilters(page);
  await search(page, 'E2E-PROD-NOIMAGE');
  await page.getByRole('link', { name: 'E2E Produkt Ohne Bild', exact: true }).click();
  await page.getByRole('tab', { name: 'Medien' }).click();
  await expect(page.getByText('Aktuelles Bild', { exact: true })).toHaveCount(0);
  await openView(page, 'products');
  await clearFilters(page);
  await search(page, 'E2E-PROD-DISCOUNT');
  await page.getByRole('link', { name: 'E2E Produkt Aktionspreis', exact: true }).click();
  await page.getByRole('tab', { name: 'Preis' }).click();
  await expect(page.locator('#jform_sale_price')).toHaveValue('50');
  await expect(page.locator('#jform_discount_price')).toHaveValue('39.99');
});

test('Categories: fixtures, status, hierarchy and form values are readable', async ({ page }) => {
  await openView(page, 'categories');
  await selectFilter(page, '#filter_published', '*');
  await expect(page.locator('#categoryList')).toContainText('E2E Hauptkategorie');
  await expect(page.locator('#categoryList')).toContainText('E2E Inaktive Kategorie');
  await selectFilter(page, '#filter_published', '1');
  await expect(page.locator('#categoryList')).toContainText('E2E Hauptkategorie');
  await expect(page.locator('#categoryList')).not.toContainText('E2E Inaktive Kategorie');
  await search(page, 'E2E Unterkategorie');
  await page.getByRole('link', { name: 'E2E Unterkategorie', exact: true }).click();
  await expect(page.locator('#jform_category_name')).toHaveValue('E2E Unterkategorie');
  await expect(page.locator('#jform_alias')).toHaveValue('e2e-child');
  await expect(page.locator('#jform_parent_id option:checked')).toHaveText('E2E Hauptkategorie');
  await openView(page, 'categories');
  await selectFilter(page, '#filter_published', '0');
  await expect(page.locator('#categoryList')).toContainText('E2E Inaktive Kategorie');
  await expect(page.locator('#categoryList')).not.toContainText('E2E Hauptkategorie');
});

test('Manufacturers: fixtures, status and form values are readable', async ({ page }) => {
  await openView(page, 'manufacturers');
  await selectFilter(page, '#filter_published', '*');
  await expect(page.locator('#manufacturerList')).toContainText('E2E Hersteller Aktiv');
  await expect(page.locator('#manufacturerList')).toContainText('E2E Hersteller Inaktiv');
  await selectFilter(page, '#filter_published', '1');
  await expect(page.locator('#manufacturerList')).toContainText('E2E Hersteller Aktiv');
  await expect(page.locator('#manufacturerList')).not.toContainText('E2E Hersteller Inaktiv');
  await search(page, 'E2E Hersteller Aktiv');
  await page.getByRole('link', { name: 'E2E Hersteller Aktiv', exact: true }).click();
  await expect(page.locator('#jform_manufacturer_name')).toHaveValue('E2E Hersteller Aktiv');
  await expect(page.locator('#jform_alias')).toHaveValue('e2e-manufacturer-active');
  await openView(page, 'manufacturers');
  await selectFilter(page, '#filter_published', '0');
  await expect(page.locator('#manufacturerList')).toContainText('E2E Hersteller Inaktiv');
  await expect(page.locator('#manufacturerList')).not.toContainText('E2E Hersteller Aktiv');
});

test('Bundles: fixtures, products and discount tiers are readable', async ({ page }) => {
  await openView(page, 'bundles');
  await selectFilter(page, '#filter_published', '');
  await expect(page.locator('#bundleList')).toContainText('E2E-BUNDLE-ACTIVE');
  await expect(page.locator('#bundleList')).toContainText('E2E-BUNDLE-INACTIVE');
  await selectFilter(page, '#filter_published', '1');
  await expect(page.locator('#bundleList')).toContainText('E2E-BUNDLE-ACTIVE');
  await expect(page.locator('#bundleList')).not.toContainText('E2E-BUNDLE-INACTIVE');
  await search(page, 'E2E-BUNDLE-ACTIVE');
  await page.getByRole('link', { name: 'E2E-BUNDLE-ACTIVE', exact: true }).click();
  await expect(page.locator('#jform_bundle_number')).toHaveValue('E2E-BUNDLE-ACTIVE');
  await page.getByRole('tab', { name: 'Produkte' }).click();
  await expect(page.locator('#bundle-product-table')).toContainText('E2E-PROD-ACTIVE');
  await page.getByRole('tab', { name: 'Rabattstufen' }).click();
  await expect(page.locator('#bundle-discount-table tbody tr')).toHaveCount(2);
  await openView(page, 'bundles');
  await selectFilter(page, '#filter_published', '0');
  await expect(page.locator('#bundleList')).toContainText('E2E-BUNDLE-INACTIVE');
  await expect(page.locator('#bundleList')).not.toContainText('E2E-BUNDLE-ACTIVE');
});

test('Coupons: implemented values and restrictions are readable', async ({ page }) => {
  await openView(page, 'coupons');
  await selectFilter(page, '#filter_published', '');
  await expect(page.locator('#couponList')).toContainText('E2E-PERCENT');
  await selectFilter(page, '#filter_published', '1');
  await expect(page.locator('#couponList')).toContainText('E2E-PERCENT');
  await selectFilter(page, '#filter_published', '0');
  await expect(page.locator('#couponList')).not.toContainText('E2E-PERCENT');
  await selectFilter(page, '#filter_published', '');
  await search(page, 'E2E-PRODUCT');
  await page.getByRole('link', { name: 'E2E Produktbeschränkt', exact: true }).click();
  await expect(page.locator('#jform_coupon_code')).toHaveValue('E2E-PRODUCT');
  await page.getByRole('tab', { name: 'Rabatt' }).click();
  await expect(page.locator('#jform_discount_type')).toHaveValue('percent');
  await expect(page.locator('#jform_discount_value')).toHaveValue('7');
  await page.getByRole('tab', { name: 'Einschränkungen' }).click();
  await expect(page.locator('#jform_product_ids option:checked')).toContainText('E2E Produkt Aktiv');
  await page.getByRole('tab', { name: 'Gültigkeit' }).click();
  await expect(page.locator('#jform_valid_to')).toHaveValue(/2035-12-31/);
});

test('Orders: snapshots and histories are readable', async ({ page }) => {
  await openView(page, 'orders');
  await expect(page.locator('#orderList')).toContainText('E2E-ORDER-NORMAL');
  await expect(page.locator('#orderList')).toContainText('E2E-ORDER-BUNDLE');
  await search(page, 'E2E-ORDER-BUNDLE');
  await page.getByRole('link', { name: 'E2E-ORDER-BUNDLE', exact: true }).click();
  await expect(page.getByText('Bestellpositionen', { exact: true })).toBeVisible();
  await expect(page.locator('main')).toContainText('E2E-PROD-DISCOUNT');
  await expect(page.locator('main')).toContainText('66,48');
  await expect(page.getByText('Status-Historie', { exact: true })).toBeVisible();
  await expect(page.locator('main')).toContainText('Künstlicher Statuswechsel');
  await expect(page.getByText('Allgemeine Historie', { exact: true })).toBeVisible();
  await expect(page.locator('main')).toContainText('E2E Bundle-Snapshot angelegt');
});

test('Configuration: settings and fixture-backed lists are readable', async ({ page }) => {
  await openView(page, 'configuration');
  await expect(page.locator('#jform_general_currency')).toBeVisible();
  await page.getByRole('tab', { name: 'Bilder' }).click();
  await expect(page.locator('#jform_image_size_mobile')).toBeVisible();
  await page.getByRole('tab', { name: 'Versand' }).click();
  await expect(page.locator('#shipmentList')).toContainText('E2E Versand Standard');
  await page.getByRole('tab', { name: 'Bezahlsystem' }).click();
  await expect(page.locator('#paymentmethodList')).toContainText('E2E Zahlung Rechnung');
  await page.getByRole('tab', { name: 'Bestellstatus' }).click();
  await expect(page.locator('#orderstatusList')).toContainText('E2E Bestellt');
});
