const { test, expect } = require('@playwright/test');
const { authenticateAdministrator, installDiagnostics, openView } = require('../support/browser');

const shipmentName = 'E2E-CRUD-SHIPMENT-REFERENCE';
const paymentName = 'E2E-CRUD-PAYMENT-REFERENCE';

test.setTimeout(120_000);
test.describe.configure({ mode: 'serial' });

test.beforeEach(async ({ page, context, baseURL }, testInfo) => {
  testInfo.diagnostics = await installDiagnostics(page, baseURL);
  await authenticateAdministrator(page, context, testInfo);
});

test.afterEach(async ({}, testInfo) => {
  testInfo.diagnostics.expectClean();
});

async function openConfigurationTab(page, tab) {
  await openView(page, 'configuration');
  await page.waitForLoadState('domcontentloaded');
  const tabButton = page.getByRole('tab', { name: tab, exact: true });
  await tabButton.click();
  await expect(tabButton).toHaveAttribute('aria-selected', 'true');
}

async function searchConfigurationList(page, { tab }) {
  await openConfigurationTab(page, tab);
}

async function selectShipmentStatus(page, value) {
  await openConfigurationTab(page, 'Versand');
  const filter = page.locator('#shipments_filter_published');
  if (await filter.inputValue() !== value) {
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
      filter.selectOption(value, { force: true }),
    ]);
  }
  await page.getByRole('tab', { name: 'Versand', exact: true }).click();
}

async function selectPaymentStatus(page, value) {
  await openConfigurationTab(page, 'Bezahlsystem');
  await page.locator('#paymentmethods_filter_published').selectOption(value, { force: true });
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
    page.locator('#paymentmethodForm').evaluate((form) => form.requestSubmit()),
  ]);
  await page.getByRole('tab', { name: 'Bezahlsystem', exact: true }).click();
}

async function matchingRow(page, table, name) {
  return page.locator(`${table} tbody tr`).filter({ hasText: name });
}

test('configuration general and image values persist and are restored', async ({ page }) => {
  await openView(page, 'configuration');
  const vat = page.locator('#jform_general_vat_rate');
  const currency = page.locator('#jform_general_currency');
  const originalVat = await vat.inputValue();
  const originalCurrency = await currency.inputValue();
  const testVat = String(Number(originalVat) + 0.25);

  await vat.fill(testVat);
  await page.getByRole('tab', { name: 'Bilder', exact: true }).click();
  const imageDefault = page.locator('#jform_image_size_default');
  const originalImageDefault = await imageDefault.inputValue();
  const testImageDefault = String(Number(originalImageDefault) + 1);

  await imageDefault.fill(testImageDefault);
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForLoadState('domcontentloaded');
  await expect(page).toHaveURL(/view=configuration/);
  await expect(vat).toHaveValue(testVat);
  await page.getByRole('tab', { name: 'Bilder', exact: true }).click();
  await expect(imageDefault).toHaveValue(testImageDefault);

  await imageDefault.fill(originalImageDefault);
  await page.getByRole('tab', { name: 'Allgemein', exact: true }).click();
  await vat.fill(originalVat);
  await currency.fill(originalCurrency);
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForLoadState('domcontentloaded');
  await openView(page, 'configuration');
  await expect(page.locator('#jform_general_vat_rate')).toHaveValue(originalVat);
  await expect(page.locator('#jform_general_currency')).toHaveValue(originalCurrency);
  await page.getByRole('tab', { name: 'Bilder', exact: true }).click();
  await expect(page.locator('#jform_image_size_default')).toHaveValue(originalImageDefault);
});

test('shipment validation, CRUD, status actions and filters', async ({ page }) => {
  await openConfigurationTab(page, 'Versand');
  await page.getByRole('link', { name: 'Hinzufügen', exact: true }).click();
  await expect(page).toHaveURL(/view=shipment.*layout=edit/);
  await page.getByRole('button', { name: 'Save & Close' }).click();
  await expect(page.locator('#jform_shipment_name')).toHaveJSProperty('validity.valid', false);

  await page.locator('#jform_shipment_name').fill(shipmentName);
  await page.locator('#jform_shipment_description').fill('CRUD shipment initial');
  await page.locator('#jform_shipment_color').fill('#123456');
  await page.locator('#jform_shipment_price').fill('6.75');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
    page.getByRole('button', { name: 'Save & Close' }).click(),
  ]);
  await expect(page).toHaveURL(/view=configuration/);

  await searchConfigurationList(page, { tab: 'Versand', search: '#shipments_filter_search', status: '#shipments_filter_published', name: shipmentName });
  await expect(page.getByRole('link', { name: shipmentName, exact: true })).toHaveCount(1);
  await page.getByRole('link', { name: shipmentName, exact: true }).click();
  const createdId = await page.locator('#jform_id').inputValue();
  expect(Number(createdId)).toBeGreaterThan(0);
  await expect(page.locator('#jform_shipment_price')).toHaveValue('6.75');

  await page.locator('#jform_shipment_description').fill('CRUD shipment after apply');
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForLoadState('domcontentloaded');
  await expect(page).toHaveURL(new RegExp(`view=shipment.*layout=edit.*id=${createdId}`));
  await expect(page.locator('#jform_id')).toHaveValue(createdId);

  await searchConfigurationList(page, { tab: 'Versand', search: '#shipments_filter_search', status: '#shipments_filter_published', name: shipmentName });
  await expect(page.getByRole('link', { name: shipmentName, exact: true })).toHaveCount(1);
  await page.getByRole('link', { name: shipmentName, exact: true }).click();
  await expect(page.locator('#jform_shipment_description')).toHaveValue('CRUD shipment after apply');
  await page.locator('#jform_shipment_description').fill('CRUD shipment after save-close');
  await page.getByRole('button', { name: 'Save & Close' }).click();
  await page.waitForLoadState('domcontentloaded');

  await searchConfigurationList(page, { tab: 'Versand', search: '#shipments_filter_search', status: '#shipments_filter_published', name: shipmentName });
  let row = await matchingRow(page, '#shipmentList', shipmentName);
  await row.getByRole('link', { name: 'Unpublish Item' }).click();
  await selectShipmentStatus(page, '*');
  await expect(page.locator('#shipmentList')).toContainText('E2E Versand Standard');
  await expect(page.locator('#shipmentList')).toContainText('E2E Versand Inaktiv');
  await selectShipmentStatus(page, '1');
  await expect(page.locator('#shipmentList')).toContainText('E2E Versand Standard');
  await expect(page.locator('#shipmentList')).not.toContainText('E2E Versand Inaktiv');
  await selectShipmentStatus(page, '0');
  await expect(page.locator('#shipmentList')).toContainText('E2E Versand Inaktiv');
  await expect(page.locator('#shipmentList')).not.toContainText('E2E Versand Standard');
  await expect(await matchingRow(page, '#shipmentList', shipmentName)).toHaveCount(1);
});

test('payment validation, CRUD, status actions and filters', async ({ page }) => {
  await openConfigurationTab(page, 'Bezahlsystem');
  await page.getByRole('link', { name: 'Hinzufügen', exact: true }).click();
  await expect(page).toHaveURL(/view=paymentmethod.*layout=edit/);
  await expect(page.locator('#jform_payment_name')).toBeVisible();
  await page.getByRole('button', { name: 'Save & Close' }).click();
  await expect(page.locator('#jform_payment_name')).toHaveJSProperty('validity.valid', false);

  await page.locator('#jform_payment_name').fill(paymentName);
  await page.locator('#jform_payment_description').fill('CRUD payment initial');
  await page.locator('#jform_payment_fee').fill('1.25');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
    page.getByRole('button', { name: 'Save & Close' }).click(),
  ]);
  await expect(page).toHaveURL(/view=configuration/);

  await openConfigurationTab(page, 'Bezahlsystem');
  await expect(page.getByRole('link', { name: paymentName, exact: true })).toHaveCount(1);
  await page.getByRole('link', { name: paymentName, exact: true }).click();
  const createdId = await page.locator('#jform_id').inputValue();
  expect(Number(createdId)).toBeGreaterThan(0);
  await expect(page.locator('#jform_payment_fee')).toHaveValue('1.25');

  await page.locator('#jform_payment_description').fill('CRUD payment after apply');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
    page.getByRole('button', { name: 'Save', exact: true }).click(),
  ]);
  await expect(page).toHaveURL(new RegExp(`view=paymentmethod.*layout=edit.*id=${createdId}`));
  await expect(page.locator('#jform_id')).toHaveValue(createdId);

  await openConfigurationTab(page, 'Bezahlsystem');
  await expect(page.getByRole('link', { name: paymentName, exact: true })).toHaveCount(1);
  await page.getByRole('link', { name: paymentName, exact: true }).click();
  await expect(page.locator('#jform_payment_description')).toHaveValue('CRUD payment after apply');
  await page.locator('#jform_payment_description').fill('CRUD payment after save-close');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
    page.getByRole('button', { name: 'Save & Close' }).click(),
  ]);

  await openConfigurationTab(page, 'Bezahlsystem');
  let row = await matchingRow(page, '#paymentmethodList', paymentName);
  await row.getByRole('link', { name: 'Unpublish Item' }).click();
  await selectPaymentStatus(page, '*');
  await expect(page.locator('#paymentmethodList')).toContainText('E2E Zahlung Rechnung');
  await expect(page.locator('#paymentmethodList')).toContainText('E2E Zahlung Inaktiv');
  await expect(page.locator('#paymentmethodList')).toContainText(paymentName);
  await selectPaymentStatus(page, '1');
  await expect(page.locator('#paymentmethodList')).toContainText('E2E Zahlung Rechnung');
  await expect(page.locator('#paymentmethodList')).not.toContainText('E2E Zahlung Inaktiv');
  await expect(page.locator('#paymentmethodList')).not.toContainText(paymentName);
  await selectPaymentStatus(page, '0');
  await expect(page.locator('#paymentmethodList')).toContainText('E2E Zahlung Inaktiv');
  await expect(page.locator('#paymentmethodList')).not.toContainText('E2E Zahlung Rechnung');
  await expect(page.locator('#paymentmethodList')).toContainText(paymentName);
  row = await matchingRow(page, '#paymentmethodList', paymentName);
  await row.getByRole('link', { name: 'Publish Item' }).click();
  await selectPaymentStatus(page, '1');
  await expect(page.locator('#paymentmethodList')).toContainText(paymentName);
});

test('existing order status supports edit, apply, save-close and restoration', async ({ page }) => {
  await searchConfigurationList(page, { tab: 'Bestellstatus', search: '#orderstatuses_filter_search', name: 'E2E Status Inaktiv' });
  await page.getByRole('link', { name: 'E2E Status Inaktiv', exact: true }).click();
  await expect(page).toHaveURL(/view=orderstatus.*layout=edit.*id=900702/);
  await expect(page.locator('#jform_id')).toHaveValue('900702');
  await page.locator('#jform_status_name').fill('E2E-CRUD-ORDERSTATUS-APPLIED');
  await page.locator('#jform_stock_action').selectOption('reserve');
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForLoadState('domcontentloaded');
  await expect(page).toHaveURL(/view=orderstatus.*layout=edit.*id=900702/);
  await expect(page.locator('#jform_id')).toHaveValue('900702');
  await expect(page.locator('#jform_status_name')).toHaveValue('E2E-CRUD-ORDERSTATUS-APPLIED');

  await page.locator('#jform_status_name').fill('E2E-CRUD-ORDERSTATUS-SAVED');
  await page.getByRole('button', { name: 'Save & Close' }).click();
  await page.waitForLoadState('domcontentloaded');
  await searchConfigurationList(page, { tab: 'Bestellstatus', search: '#orderstatuses_filter_search', name: 'E2E-CRUD-ORDERSTATUS-SAVED' });
  await expect(page.getByRole('link', { name: 'E2E-CRUD-ORDERSTATUS-SAVED', exact: true })).toHaveCount(1);
  await page.getByRole('link', { name: 'E2E-CRUD-ORDERSTATUS-SAVED', exact: true }).click();
  await expect(page.locator('#jform_stock_action')).toHaveValue('reserve');

  await page.locator('#jform_status_name').fill('E2E Status Inaktiv');
  await page.locator('#jform_stock_action').selectOption('none');
  await page.getByRole('button', { name: 'Save & Close' }).click();
  await page.waitForLoadState('domcontentloaded');
  await searchConfigurationList(page, { tab: 'Bestellstatus', search: '#orderstatuses_filter_search', name: 'E2E Status Inaktiv' });
  await expect(page.getByRole('link', { name: 'E2E Status Inaktiv', exact: true })).toHaveCount(1);
});
