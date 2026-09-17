const { test, expect } = require('@playwright/test');
const { authenticateAdministrator, installDiagnostics, openView } = require('../support/browser');

test.describe.configure({ mode: 'serial' });
test.setTimeout(120_000);

test.beforeEach(async ({ page, context, baseURL }, testInfo) => {
  testInfo.diagnostics = await installDiagnostics(page, baseURL);
  await authenticateAdministrator(page, context, testInfo);
});
test.afterEach(async ({}, testInfo) => testInfo.diagnostics.expectClean());

test('seven immutable system filters and category-aware administration work', async ({ page }) => {
  await openView(page, 'filters');
  const list = page.locator('table.itemList tbody');
  await expect(list.locator('tr')).toHaveCount(7);
  await expect(list).toContainText('manufacturer');
  await expect(list).toContainText('availability');
  await expect(list).toContainText('duration');
  await expect(list).toContainText('caliber');
  await expect(list).toContainText('nem');
  await expect(list).toContainText('firing_type');
  await expect(list).toContainText('product_type');

  await page.getByRole('link', { name: 'NEM', exact: true }).click();
  await expect(page.locator('#jform_filter_key')).toHaveValue('nem');
  await expect(page.locator('#jform_filter_key')).toHaveAttribute('readonly');
  const staleLabels = page.locator('#fdshop-filter-ranges input[name$="[label]"]');
  let cleaned = false;
  for (let i = 0; i < await staleLabels.count(); i += 1) {
    if ((await staleLabels.nth(i).inputValue()).startsWith('E2E ')) {
      await staleLabels.nth(i).locator('xpath=ancestor::tr').locator('input[name$="[delete]"]').check();
      cleaned = true;
    }
  }
  if (cleaned) {
    await page.evaluate(() => Joomla.submitbutton('filter.apply'));
    await page.waitForLoadState('networkidle');
  }
  await expect(page.locator('#fdshop-filter-ranges tbody tr')).toHaveCount(3);
  await page.locator('[data-add-range]').click();
  let row = page.locator('#fdshop-filter-ranges tbody tr').last();
  await row.locator('input[name$="[label]"]').fill('E2E 500 bis 600 g');
  await row.locator('input[name$="[value_from]"]').fill('500');
  await row.locator('input[name$="[value_to]"]').fill('600');
  await page.evaluate(() => Joomla.submitbutton('filter.apply'));
  await page.waitForLoadState('networkidle');
  await expect(page.getByText('Filterkonfiguration gespeichert.', { exact: true })).toBeVisible();
  await expect(page.locator('#fdshop-filter-ranges tbody tr')).toHaveCount(4);

  await page.locator('[data-add-range]').click();
  row = page.locator('#fdshop-filter-ranges tbody tr').last();
  await row.locator('input[name$="[label]"]').fill('E2E Kollision');
  await row.locator('input[name$="[value_from]"]').fill('550');
  await row.locator('input[name$="[value_to]"]').fill('650');
  await page.evaluate(() => Joomla.submitbutton('filter.apply'));
  await page.waitForLoadState('networkidle');
  await expect(page.getByText(/überschneiden sich/)).toBeVisible();

  const created = page.locator('#fdshop-filter-ranges input[value="E2E 500 bis 600 g"]').locator('xpath=ancestor::tr');
  await created.locator('input[name$="[delete]"]').check();
  await page.evaluate(() => Joomla.submitbutton('filter.apply'));
  await page.waitForLoadState('networkidle');
  await expect(page.locator('#fdshop-filter-ranges tbody tr')).toHaveCount(3);
});
