const { test, expect } = require('@playwright/test');
const fs = require('node:fs');
const { authenticateAdministrator, installDiagnostics, openView } = require('../support/browser');

const productName = 'E2E-CRUD-PRODUCT-REFERENCE';
const productSku = 'E2E-CRUD-PRODUCT-REFERENCE';
const fixtureImageProduct = 'E2E Produkt Bild';

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

async function searchProduct(page, value, { published = '*', deleted = '0' } = {}) {
  await openView(page, 'products');
  await clearFilters(page);
  const options = page.getByRole('button', { name: 'Filter Options' });
  if (await options.isVisible()) await options.click();
  await page.locator('#filter_published').selectOption(published, { force: true });
  await page.locator('#filter_deleted').selectOption(deleted, { force: true });
  await page.waitForLoadState('networkidle');
  await page.locator('#filter_search').fill(value);
  await page.locator('#filter_search').press('Enter');
  await page.waitForLoadState('networkidle');
}

function matchingRow(page, name) {
  return page.locator('#productList tbody tr').filter({ hasText: name });
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

async function setRadio(page, field, value) {
  const radio = page.locator(`input[name="jform[${field}]"][value="${value}"]`);
  const id = await radio.getAttribute('id');

  await page.locator(`label[for="${id}"]`).click();
  await expect(radio).toBeChecked();
}

test('product meta helper uses current unsaved values and protects manual content', async ({ page }) => {
  await openView(page, 'product&layout=edit');
  await page.getByRole('tab', { name: 'Beschreibung' }).click();
  await expect(page.locator('#jform_meta_keywords')).toHaveCount(0);
  await expect(page.locator('[data-fdshop-meta-counter="title"]')).toHaveText('0 Zeichen');

  let message = '';
  page.once('dialog', async dialog => { message = dialog.message(); await dialog.accept(); });
  await page.getByRole('button', { name: 'Meta-Daten füllen' }).click();
  expect(message).toBe('Bitte zuerst einen Produkttyp auswählen.');
  await expect(page.locator('#jform_meta_title')).toHaveValue('');

  await page.locator('#jform_meta_product_type').selectOption('fireworks_battery');
  page.once('dialog', async dialog => { message = dialog.message(); await dialog.accept(); });
  await page.getByRole('button', { name: 'Meta-Daten füllen' }).click();
  expect(message).toBe('Bitte zuerst einen Produktnamen eingeben.');

  await page.getByRole('tab', { name: 'Allgemein' }).click();
  await page.locator('#jform_product_name').fill('Acryl');
  await page.locator('#jform_manufacturer_id').selectOption('900001');
  await page.locator('#jform_buyer_group_id').selectOption('900020');
  await page.getByRole('tab', { name: 'Beschreibung' }).click();
  await page.locator('#jform_short_description').fill('<strong>Blauer</strong> Feuertopf mit   Sternen.');
  await page.getByRole('tab', { name: 'Besondere Felder' }).click();
  await page.locator('#jform_shot_count').fill('13');
  await page.locator('#jform_caliber').fill('30 mm');
  await page.locator('#jform_nem').fill('325');
  await page.locator('#jform_burn_time').fill('21 s');
  await page.getByRole('tab', { name: 'Beschreibung' }).click();
  await page.getByRole('button', { name: 'Meta-Daten füllen' }).click();
  await expect(page.locator('#jform_meta_title')).toHaveValue('E2E Hersteller Aktiv Acryl | Feuerwerksbatterie');
  await expect(page.locator('#jform_meta_description')).toHaveValue('Acryl von E2E Hersteller Aktiv: Blauer Feuertopf mit Sternen. 13 Schuss · 30 mm · 325 g NEM · 21 s.');
  await expect(page.locator('#jform_meta_description')).not.toHaveValue(/mm mm|g g|s s/);

  await page.locator('#jform_meta_title').fill('Manuell optimiert');
  await page.locator('#jform_meta_description').fill('Manuelle Beschreibung');
  page.once('dialog', dialog => dialog.dismiss());
  await page.getByRole('button', { name: 'Meta-Daten füllen' }).click();
  await expect(page.locator('#jform_meta_title')).toHaveValue('Manuell optimiert');
  await expect(page.locator('#jform_meta_description')).toHaveValue('Manuelle Beschreibung');
  page.once('dialog', dialog => dialog.accept());
  await page.getByRole('button', { name: 'Meta-Daten füllen' }).click();
  await expect(page.locator('#jform_meta_title')).toHaveValue('E2E Hersteller Aktiv Acryl | Feuerwerksbatterie');

  await page.locator('#jform_meta_title').fill('x'.repeat(61));
  await expect(page.locator('[data-fdshop-meta-counter="title"]')).toContainText('61 Zeichen – möglicherweise zu lang');
  await expect(page.locator('#jform_meta_title')).toBeEditable();

  await page.locator('#jform_meta_product_type').evaluate(select => {
    select.add(new Option('Manipuliert', 'invalid-type'));
    select.value = 'invalid-type';
  });
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForLoadState('networkidle');
  await expect(page.locator('#system-message-container')).toContainText('Ungültiger Produkttyp für Meta-Daten');
  await expect(page.locator('#jform_id')).toHaveValue('0');
});

test('product invalid save, create, apply, save-close, mappings, stock, status and upload', async ({ page }, testInfo) => {
  await openView(page, 'products');
  await page.getByRole('button', { name: 'New' }).click();
  await expect(page).toHaveURL(/view=product.*layout=edit/);
  await page.getByRole('button', { name: 'Save & Close' }).click();
  await expect(page).toHaveURL(/view=product.*layout=edit/);
  await expect(page.locator('#jform_product_name')).toHaveJSProperty('validity.valid', false);
  await searchProduct(page, productSku);
  await expect(page.getByRole('link', { name: productName, exact: true })).toHaveCount(0);

  await openView(page, 'product&layout=edit');
  await page.locator('#jform_product_name').fill(productName);
  await page.locator('#jform_sku').fill(productSku);
  await page.locator('#jform_gtin').fill('9900000099991');
  await page.locator('#jform_alias').fill('e2e-crud-product-reference');
  await expect(page.locator('#jform_bundle_eligible')).toBeVisible();
  expect(await page.locator('#jform_bundle_eligible').evaluate((field) =>
    Boolean(field.compareDocumentPosition(document.querySelector('#jform_manufacturer_id')) & Node.DOCUMENT_POSITION_FOLLOWING)
  )).toBe(true);
  await setRadio(page, 'bundle_eligible', '1');
  await page.locator('#jform_manufacturer_id').selectOption('900001');
  await page.locator('#jform_category_ids').selectOption(['900010']);
  await page.locator('#jform_buyer_group_id').selectOption('900020');
  await setRadio(page, 'is_active', '1');

  await page.getByRole('tab', { name: 'Preis' }).click();
  await page.locator('#jform_sale_price').fill('31.25');
  await page.locator('#jform_discount_price').fill('27.50');
  await setRadio(page, 'discount_active', '1');
  await page.locator('#jform_currency').fill('EUR');

  await page.getByRole('tab', { name: 'Lager' }).click();
  await page.locator('#jform_stock_quantity').fill('12');
  await page.locator('#jform_low_stock').fill('4');
  await page.locator('#jform_reserved_quantity').fill('2');
  await page.locator('#jform_sold_quantity').fill('3');
  await setRadio(page, 'is_in_stock', '1');
  await page.locator('#jform_min_order_qty').fill('1');
  await page.locator('#jform_max_order_qty').fill('8');
  await page.locator('#jform_step_order_qty').fill('1');

  await page.getByRole('tab', { name: 'Beschreibung' }).click();
  await page.locator('#jform_short_description').fill('CRUD initial short description');
  await page.locator('#jform_description').fill('CRUD initial product description');
  await page.locator('#jform_meta_product_type').selectOption('fireworks_battery');
  await page.locator('#jform_meta_title').fill('CRUD initial meta title');
  await page.locator('#jform_meta_description').fill('CRUD initial meta description');

  await page.getByRole('tab', { name: 'Besondere Felder' }).click();
  await expect(page.locator('#jform_bundle_eligible')).toBeHidden();
  await page.locator('#jform_unit_type').selectOption('Display');
  await page.locator('#jform_unit_quantity').fill('6');
  await page.locator('#jform_unit_discount_type').selectOption('percent');
  await page.locator('#jform_unit_discount_value').fill('5');
  await expect(page.locator('[data-fdshop-package-preview]')).toContainText('Verpackungspreis');

  const imagePath = testInfo.outputPath('e2e-crud-product.png');
  await page.screenshot({ path: imagePath });
  await page.getByRole('tab', { name: 'Medien' }).click();
  const alphaPath = testInfo.outputPath('e2e-alpha.png');
  const alphaData = await page.evaluate(() => {
    const canvas = document.createElement('canvas');
    canvas.width = 40;
    canvas.height = 20;
    const context = canvas.getContext('2d');
    context.clearRect(0, 0, 40, 20);
    context.fillStyle = 'rgba(255, 0, 0, .5)';
    context.fillRect(10, 5, 20, 10);
    return canvas.toDataURL('image/png').split(',')[1];
  });
  fs.writeFileSync(alphaPath, Buffer.from(alphaData, 'base64'));
  await page.locator('#jform_product_image').setInputFiles(imagePath);
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(/view=product.*layout=edit.*id=\d+/);
  const createdId = await page.locator('#jform_id').inputValue();
  expect(Number(createdId)).toBeGreaterThan(0);
  await page.getByRole('tab', { name: 'Medien' }).click();
  await expect(page.getByText('Produktbilder', { exact: true })).toBeVisible();
  const initialImage = page.locator('[data-fdshop-media-item] img');
  await expect(initialImage).toBeVisible();
  await expect(initialImage).toHaveAttribute('src', /\/images\/FDShop\/products\/small\/.*\.webp$/);
  const processedSize = await initialImage.evaluate(image => ({ width: image.naturalWidth, height: image.naturalHeight }));
  expect(processedSize.width).toBeGreaterThan(190);
  expect(processedSize.width).toBeLessThanOrEqual(200);
  expect(processedSize.height).toBeGreaterThan(100);
  expect(processedSize.height).toBeLessThanOrEqual(176);

  await page.locator('#jform_product_image').setInputFiles(alphaPath);
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForLoadState('networkidle');
  await page.getByRole('tab', { name: 'Medien' }).click();
  await expect(page.locator('[data-fdshop-media-item]')).toHaveCount(2);
  await page.locator('[data-fdshop-media-item]').nth(1).getByRole('button', { name: 'Als Hauptbild' }).click();
  await page.waitForLoadState('networkidle');
  await page.getByRole('tab', { name: 'Medien' }).click();
  await expect(page.locator('[data-fdshop-media-item]').nth(0).getByText('Hauptbild', { exact: true })).toBeVisible();
  const alphaImage = page.locator('[data-fdshop-media-item]').nth(0).locator('img');
  expect(await alphaImage.evaluate(async image => {
    if (!image.complete) await new Promise(resolve => image.addEventListener('load', resolve, { once: true }));
    const canvas = document.createElement('canvas');
    canvas.width = image.naturalWidth;
    canvas.height = image.naturalHeight;
    canvas.getContext('2d').drawImage(image, 0, 0);
    return { width: image.naturalWidth, height: image.naturalHeight, cornerAlpha: canvas.getContext('2d').getImageData(0, 0, 1, 1).data[3] };
  })).toEqual({ width: 40, height: 20, cornerAlpha: 0 });
  await page.locator('[data-fdshop-media-item]').nth(1).locator('[data-fdshop-media-ordering]').fill('0');
  await page.locator('[data-fdshop-media-item]').nth(1).getByRole('button', { name: 'Reihenfolge speichern' }).click();
  await page.waitForLoadState('networkidle');
  await page.getByRole('tab', { name: 'Medien' }).click();
  page.once('dialog', dialog => dialog.accept());
  await page.locator('[data-fdshop-media-item]').nth(1).getByRole('button', { name: 'Bild löschen' }).click();
  await page.waitForLoadState('networkidle');
  await page.getByRole('tab', { name: 'Medien' }).click();
  await expect(page.locator('[data-fdshop-media-item]')).toHaveCount(1);

  await page.getByRole('tab', { name: 'Allgemein' }).click();
  await expect(page.locator('#jform_manufacturer_id option:checked')).toHaveText('E2E Hersteller Aktiv');
  await expect(page.locator('#jform_category_ids')).toHaveValues(['900010']);
  await expect(page.locator('#jform_buyer_group_id')).toHaveValue('900020');
  await page.locator('#jform_category_ids').selectOption(['900010', '900012']);
  await page.locator('#jform_buyer_group_id').selectOption('900021');
  await page.getByRole('tab', { name: 'Preis' }).click();
  await page.locator('#jform_sale_price').fill('32.50');
  await page.getByRole('tab', { name: 'Lager' }).click();
  await page.locator('#jform_stock_quantity').fill('3');
  await page.getByRole('tab', { name: 'Beschreibung' }).click();
  await page.locator('#jform_short_description').fill('CRUD description after apply');
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(new RegExp(`view=product.*layout=edit.*id=${createdId}`));
  await expect(page.locator('#jform_id')).toHaveValue(createdId);
  await page.getByRole('tab', { name: 'Allgemein' }).click();
  await expect(page.locator('#jform_category_ids')).toHaveValues(['900010', '900012']);
  await expect(page.locator('#jform_buyer_group_id')).toHaveValue('900021');
  await page.getByRole('tab', { name: 'Lager' }).click();
  await expect(page.locator('#jform_stock_quantity')).toHaveValue('3');
  await expect(page.locator('#jform_in_stock')).toHaveValue('Verfügbar');
  await page.getByRole('tab', { name: 'Besondere Felder' }).click();
  await expect(page.locator('#jform_unit_type')).toHaveValue('Display');
  await expect(page.locator('#jform_unit_quantity')).toHaveValue('6');
  await expect(page.locator('#jform_unit_discount_type')).toHaveValue('percent');
  await expect(page.locator('#jform_unit_discount_value')).toHaveValue('5');

  await page.getByRole('tab', { name: 'Beschreibung' }).click();
  await expect(page.locator('#jform_meta_title')).toHaveValue('CRUD initial meta title');
  await expect(page.locator('#jform_meta_description')).toHaveValue('CRUD initial meta description');
  await expect(page.locator('#jform_meta_product_type')).toHaveValue('fireworks_battery');
  await page.locator('#jform_meta_title').fill('CRUD meta title after save-close');
  await page.getByRole('button', { name: 'Save & Close' }).click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(/view=products/);

  await searchProduct(page, productSku);
  await page.getByRole('link', { name: productName, exact: true }).click();
  await expect(page.locator('#jform_id')).toHaveValue(createdId);
  await page.getByRole('tab', { name: 'Preis' }).click();
  await expect(page.locator('#jform_sale_price')).toHaveValue('32.5');
  await expect(page.locator('#jform_discount_price')).toHaveValue('27.5');
  await page.getByRole('tab', { name: 'Medien' }).click();
  await expect(page.getByText('Produktbilder', { exact: true })).toBeVisible();

  await page.goto(`/index.php?option=com_fdshop&view=product&id=${createdId}`);
  await expect(page).toHaveTitle('CRUD meta title after save-close');
  await expect(page.locator('meta[name="description"]')).toHaveAttribute('content', 'CRUD initial meta description');
  await expect(page.locator('meta[name="keywords"]')).toHaveCount(0);

  await searchProduct(page, productSku);
  let row = matchingRow(page, productName);
  await row.getByRole('link', { name: 'Unpublish Item' }).click();
  await searchProduct(page, productSku, { published: '0' });
  row = matchingRow(page, productName);
  await expect(row.getByRole('link', { name: 'Publish Item' })).toBeVisible();
  await row.getByRole('link', { name: 'Publish Item' }).click();
  await searchProduct(page, productSku, { published: '1' });
  await expect(matchingRow(page, productName).getByRole('link', { name: 'Unpublish Item' })).toBeVisible();
});

test('fixture image survives save and referenced product is protected from permanent deletion', async ({ page }) => {
  await searchProduct(page, 'E2E-PROD-IMAGE');
  await page.getByRole('link', { name: fixtureImageProduct, exact: true }).click();
  await page.getByRole('tab', { name: 'Medien' }).click();
  await expect(page.locator('img[src*="e2e-fixture-product.svg"]')).toBeVisible();
  await page.getByRole('tab', { name: 'Beschreibung' }).click();
  await page.locator('#jform_description').fill('Temporary image-preservation check');
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForLoadState('networkidle');
  await page.getByRole('tab', { name: 'Medien' }).click();
  await expect(page.locator('img[src*="e2e-fixture-product.svg"]')).toBeVisible();
  await page.getByRole('tab', { name: 'Beschreibung' }).click();
  await page.locator('#jform_description').fill('Mit synthetischem Bild');
  await page.getByRole('button', { name: 'Save & Close' }).click();
  await page.waitForLoadState('networkidle');

  await searchProduct(page, 'E2E-PROD-ACTIVE');
  let row = matchingRow(page, 'E2E Produkt Aktiv');
  await row.getByRole('checkbox', { name: 'Select' }).check();
  await acceptDelete(page, () => page.getByRole('button', { name: 'Trash' }).click());
  await searchProduct(page, 'E2E-PROD-ACTIVE', { deleted: '1' });
  row = matchingRow(page, 'E2E Produkt Aktiv');
  await row.getByRole('checkbox', { name: 'Select' }).check();
  await acceptDelete(page, () => page.getByRole('button', { name: 'Endgültig löschen' }).click());
  await expect(page.locator('#system-message-container')).toContainText('kann nicht endgültig gelöscht werden');
  await expect(matchingRow(page, 'E2E Produkt Aktiv')).toHaveCount(1);
  await matchingRow(page, 'E2E Produkt Aktiv').getByRole('checkbox', { name: 'Select' }).check();
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }),
    page.getByRole('button', { name: 'Wiederherstellen' }).click(),
  ]);
  await searchProduct(page, 'E2E-PROD-ACTIVE');
  await expect(matchingRow(page, 'E2E Produkt Aktiv')).toHaveCount(1);
});

test('product trash, restore and permanent cleanup', async ({ page }) => {
  await searchProduct(page, productSku);
  let row = matchingRow(page, productName);
  await row.getByRole('checkbox', { name: 'Select' }).check();
  await acceptDelete(page, () => page.getByRole('button', { name: 'Trash' }).click());
  await searchProduct(page, productSku, { deleted: '1' });
  await expect(matchingRow(page, productName)).toHaveCount(1);
  await matchingRow(page, productName).getByRole('checkbox', { name: 'Select' }).check();
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }),
    page.getByRole('button', { name: 'Wiederherstellen' }).click(),
  ]);
  await searchProduct(page, productSku);
  await expect(matchingRow(page, productName)).toHaveCount(1);
  await matchingRow(page, productName).getByRole('checkbox', { name: 'Select' }).check();
  await acceptDelete(page, () => page.getByRole('button', { name: 'Trash' }).click());
  await searchProduct(page, productSku, { deleted: '1' });
  await matchingRow(page, productName).getByRole('checkbox', { name: 'Select' }).check();
  await acceptDelete(page, () => page.getByRole('button', { name: 'Endgültig löschen' }).click());
  await searchProduct(page, productSku, { deleted: '1' });
  await expect(matchingRow(page, productName)).toHaveCount(0);
});
