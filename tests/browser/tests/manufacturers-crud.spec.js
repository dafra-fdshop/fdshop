const { test, expect } = require('@playwright/test');
const { authenticateAdministrator, installDiagnostics, openView } = require('../support/browser');

const manufacturerName = 'E2E-CRUD-MANUFACTURER-REFERENCE';
const initialAlias = 'e2e-crud-manufacturer-reference';

test.setTimeout(90_000);

test.beforeEach(async ({ page, context, baseURL }, testInfo) => {
  testInfo.diagnostics = await installDiagnostics(page, baseURL);
  await authenticateAdministrator(page, context, testInfo);
});

test.afterEach(async ({}, testInfo) => {
  testInfo.diagnostics.expectClean();
});

async function searchManufacturer(page, name, status = 'Published') {
  await openView(page, 'manufacturers');
  const clearButton = page.getByRole('button', { name: 'Clear' });
  if (await clearButton.isVisible() && await clearButton.isEnabled()) {
    await clearButton.click();
    await page.waitForLoadState('networkidle');
  }
  const optionsButton = page.getByRole('button', { name: 'Filter Options' });
  if (await optionsButton.isVisible()) {
    await optionsButton.click();
  }
  await page.locator('#filter_published').selectOption({ label: status }, { force: true });
  await page.waitForLoadState('networkidle');

  const search = page.locator('#filter_search');
  await expect(search).toBeVisible();
  await search.fill(name);
  await search.press('Enter');
  await page.waitForLoadState('networkidle');
}

async function matchingRow(page, name) {
  return page.locator('#manufacturerList tbody tr').filter({ hasText: name });
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

test('manufacturer create, apply, save-close, status, protection and cleanup', async ({ page }) => {
  await openView(page, 'manufacturers');
  await page.getByRole('button', { name: 'New' }).click();
  await expect(page).toHaveURL(/view=manufacturer.*layout=edit/);

  await page.getByRole('button', { name: 'Save & Close' }).click();
  await expect(page).toHaveURL(/view=manufacturer.*layout=edit/);
  await expect(page.locator('#jform_manufacturer_name')).toHaveJSProperty('validity.valid', false);

  await searchManufacturer(page, manufacturerName);
  await expect(page.getByRole('link', { name: manufacturerName, exact: true })).toHaveCount(0);

  await openView(page, 'manufacturer&layout=edit');
  await page.locator('#jform_manufacturer_name').fill(manufacturerName);
  await page.locator('#jform_alias').fill(initialAlias);
  await page.locator('#jform_description').fill('CRUD initial description');
  await page.locator('#jform_meta_title').fill('CRUD initial meta title');
  await page.getByRole('button', { name: 'Save & Close' }).click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(/view=manufacturers/);
  await expect(page.locator('#system-message-container')).toContainText('Hersteller gespeichert.');

  await searchManufacturer(page, manufacturerName);
  await expect(page.getByRole('link', { name: manufacturerName, exact: true })).toHaveCount(1);
  await page.getByRole('link', { name: manufacturerName, exact: true }).click();
  await expect(page).toHaveURL(/view=manufacturer.*layout=edit.*id=\d+/);
  const createdId = await page.locator('#jform_id').inputValue();
  expect(Number(createdId)).toBeGreaterThan(0);
  await expect(page.locator('#jform_alias')).toHaveValue(initialAlias);
  await expect(page.locator('#jform_description')).toHaveValue('CRUD initial description');

  await page.locator('#jform_description').fill('CRUD description after apply');
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(new RegExp(`view=manufacturer.*layout=edit.*id=${createdId}`));
  await expect(page.locator('#jform_id')).toHaveValue(createdId);
  await expect(page.locator('#jform_description')).toHaveValue('CRUD description after apply');

  await searchManufacturer(page, manufacturerName);
  await expect(page.getByRole('link', { name: manufacturerName, exact: true })).toHaveCount(1);
  await page.getByRole('link', { name: manufacturerName, exact: true }).click();
  await expect(page.locator('#jform_description')).toHaveValue('CRUD description after apply');
  await page.locator('#jform_meta_title').fill('CRUD meta title after save-close');
  await page.getByRole('button', { name: 'Save & Close' }).click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(/view=manufacturers/);

  await searchManufacturer(page, manufacturerName);
  await expect(page.getByRole('link', { name: manufacturerName, exact: true })).toHaveCount(1);
  let row = await matchingRow(page, manufacturerName);
  await row.getByRole('link', { name: 'Unpublish Item' }).click();
  await searchManufacturer(page, manufacturerName, 'Unpublished');
  row = await matchingRow(page, manufacturerName);
  await expect(row.getByRole('link', { name: 'Publish Item' })).toBeVisible();
  await row.getByRole('link', { name: 'Publish Item' }).click();
  await searchManufacturer(page, manufacturerName);
  await expect((await matchingRow(page, manufacturerName)).getByRole('link', { name: 'Unpublish Item' })).toBeVisible();

  await searchManufacturer(page, 'E2E Hersteller Aktiv');
  row = await matchingRow(page, 'E2E Hersteller Aktiv');
  await row.getByRole('checkbox', { name: 'Select' }).check();
  await acceptDelete(page, () => page.getByRole('button', { name: 'Delete' }).click());
  await expect(page.locator('#system-message-container')).toContainText('kann nicht gelöscht werden');
  await expect(page.getByRole('link', { name: 'E2E Hersteller Aktiv', exact: true })).toHaveCount(1);

  await searchManufacturer(page, manufacturerName);
  row = await matchingRow(page, manufacturerName);
  await row.getByRole('checkbox', { name: 'Select' }).check();
  await acceptDelete(page, () => page.getByRole('button', { name: 'Delete' }).click());
  await searchManufacturer(page, manufacturerName);
  await expect(page.getByRole('link', { name: manufacturerName, exact: true })).toHaveCount(0);
});
