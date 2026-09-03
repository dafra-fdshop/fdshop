const { test, expect } = require('@playwright/test');
const { authenticateAdministrator, installDiagnostics, openView } = require('../support/browser');

const categoryName = 'E2E-CRUD-CATEGORY-REFERENCE';
const categoryAlias = 'e2e-crud-category-reference';

test.setTimeout(90_000);
test.describe.configure({ mode: 'serial' });

test.beforeEach(async ({ page, context, baseURL }, testInfo) => {
  testInfo.diagnostics = await installDiagnostics(page, baseURL);
  await authenticateAdministrator(page, context, testInfo);
});

test.afterEach(async ({}, testInfo) => {
  testInfo.diagnostics.expectClean();
});

async function searchCategory(page, name, status = 'All') {
  await openView(page, 'categories');
  const clearButton = page.getByRole('button', { name: 'Clear' });
  if (await clearButton.isVisible() && await clearButton.isEnabled()) {
    await clearButton.click();
    await page.waitForLoadState('networkidle');
  }
  const optionsButton = page.getByRole('button', { name: 'Filter Options' });
  if (await optionsButton.isVisible()) await optionsButton.click();
  await page.locator('#filter_published').selectOption({ label: status }, { force: true });
  await page.waitForLoadState('networkidle');
  const search = page.locator('#filter_search');
  await expect(search).toBeVisible();
  await search.fill(name);
  await search.press('Enter');
  await page.waitForLoadState('networkidle');
}

async function matchingRow(page, name) {
  return page.locator('#categoryList tbody tr').filter({ hasText: name });
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

test('category validation, create, apply, save-close, status and protection', async ({ page }) => {
  await openView(page, 'categories');
  await page.getByRole('button', { name: 'New' }).click();
  await expect(page).toHaveURL(/view=category.*layout=edit/);

  await page.getByRole('button', { name: 'Save & Close' }).click();
  await expect(page).toHaveURL(/view=category.*layout=edit/);
  await expect(page.locator('#jform_category_name')).toHaveJSProperty('validity.valid', false);

  await searchCategory(page, categoryName);
  await expect(page.getByRole('link', { name: categoryName, exact: true })).toHaveCount(0);

  await openView(page, 'category&layout=edit');
  await page.locator('#jform_category_name').fill(categoryName);
  await page.locator('#jform_alias').fill(categoryAlias);
  await page.locator('#jform_description').fill('CRUD initial category description');
  await page.getByRole('button', { name: 'Save & Close' }).click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(/view=categories/);

  await searchCategory(page, categoryName);
  await expect(page.getByRole('link', { name: categoryName, exact: true })).toHaveCount(1);
  await page.getByRole('link', { name: categoryName, exact: true }).click();
  await expect(page).toHaveURL(/view=category.*layout=edit.*id=\d+/);
  const createdId = await page.locator('#jform_id').inputValue();
  expect(Number(createdId)).toBeGreaterThan(0);
  await expect(page.locator('#jform_alias')).toHaveValue(categoryAlias);
  await expect(page.locator('#jform_description')).toHaveValue('CRUD initial category description');

  await page.locator('#jform_description').fill('CRUD category description after apply');
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(new RegExp(`view=category.*layout=edit.*id=${createdId}`));
  await expect(page.locator('#jform_id')).toHaveValue(createdId);
  await expect(page.locator('#jform_description')).toHaveValue('CRUD category description after apply');

  await searchCategory(page, categoryName);
  await expect(page.getByRole('link', { name: categoryName, exact: true })).toHaveCount(1);
  await page.getByRole('link', { name: categoryName, exact: true }).click();
  await expect(page.locator('#jform_description')).toHaveValue('CRUD category description after apply');
  await page.locator('#jform_description').fill('CRUD category description after save-close');
  await page.getByRole('button', { name: 'Save & Close' }).click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(/view=categories/);

  await searchCategory(page, categoryName);
  await expect(page.getByRole('link', { name: categoryName, exact: true })).toHaveCount(1);
  let row = await matchingRow(page, categoryName);
  await row.getByRole('link', { name: 'Unpublish Item' }).click();
  await searchCategory(page, categoryName, 'Unpublished');
  row = await matchingRow(page, categoryName);
  await expect(row.getByRole('link', { name: 'Publish Item' })).toBeVisible();

  await searchCategory(page, 'E2E Hauptkategorie');
  row = await matchingRow(page, 'E2E Hauptkategorie');
  await row.getByRole('checkbox', { name: 'Select' }).check();
  await acceptDelete(page, () => page.getByRole('button', { name: 'Delete' }).click());
  await expect(page.locator('#system-message-container')).toContainText('kann nicht gelöscht werden');
  await expect(page.getByRole('link', { name: 'E2E Hauptkategorie', exact: true })).toHaveCount(1);
});

test('category cleanup removes the dedicated CRUD record through the UI', async ({ page }) => {
  await searchCategory(page, categoryName, 'Unpublished');
  const row = await matchingRow(page, categoryName);
  await expect(row).toHaveCount(1);
  await row.getByRole('checkbox', { name: 'Select' }).check();
  await acceptDelete(page, () => page.getByRole('button', { name: 'Delete' }).click());
  await searchCategory(page, categoryName);
  await expect(page.getByRole('link', { name: categoryName, exact: true })).toHaveCount(0);
});
