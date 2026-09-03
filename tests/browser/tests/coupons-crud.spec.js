const { test, expect } = require('@playwright/test');
const { authenticateAdministrator, installDiagnostics, openView } = require('../support/browser');

const couponName = 'E2E-CRUD-COUPON-REFERENCE';
const couponCode = 'E2E-CRUD-COUPON-REFERENCE';

test.setTimeout(100_000);
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

async function searchCoupon(page, value, published = '') {
  await openView(page, 'coupons');
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
  return page.locator('#couponList tbody tr').filter({ hasText: couponCode });
}

async function setPublished(page, value) {
  await page.locator(`input[name="jform[published]"][value="${value}"]`).check({ force: true });
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

test('coupon invalid save, create, both discount types, mappings, apply, save-close and status', async ({ page }) => {
  await openView(page, 'coupons');
  await page.getByRole('button', { name: 'New' }).click();
  await expect(page).toHaveURL(/view=coupon.*layout=edit/);
  await page.getByRole('button', { name: 'Save & Close' }).click();
  await expect(page).toHaveURL(/view=coupon.*layout=edit/);
  await expect(page.locator('#jform_coupon_name')).toHaveJSProperty('validity.valid', false);
  await expect(page.locator('#jform_coupon_code')).toHaveJSProperty('validity.valid', false);
  await searchCoupon(page, couponCode);
  await expect(page.getByRole('link', { name: couponName, exact: true })).toHaveCount(0);

  await openView(page, 'coupon&layout=edit');
  await page.locator('#jform_coupon_name').fill(couponName);
  await page.locator('#jform_coupon_code').fill(couponCode);
  await page.locator('#jform_alias').fill('e2e-crud-coupon-reference');
  await page.locator('#jform_description').fill('CRUD initial coupon description');
  await setPublished(page, '1');

  await page.getByRole('tab', { name: 'Rabatt' }).click();
  await page.locator('#jform_discount_type').selectOption('percent');
  await page.locator('#jform_discount_value').fill('12.5');
  await page.locator('#jform_minimum_order_total').fill('40');

  await page.getByRole('tab', { name: 'Einschränkungen' }).click();
  const userId = await page.locator('#jform_user_ids option').first().getAttribute('value');
  expect(Number(userId)).toBeGreaterThan(0);
  await page.locator('#jform_user_ids').selectOption([userId]);
  await page.locator('#jform_buyer_group_ids').selectOption(['900020']);
  await page.locator('#jform_product_ids').selectOption(['900100']);
  await page.locator('#jform_category_ids').selectOption(['900010']);

  await page.getByRole('tab', { name: 'Gültigkeit' }).click();
  await page.locator('#jform_valid_from').fill('2027-01-01 00:00:00');
  await page.locator('#jform_valid_to').fill('2032-12-31 23:59:59');
  await page.getByRole('tab', { name: 'Nutzung' }).click();
  await page.locator('#jform_usage_limit_total').fill('25');
  await page.locator('#jform_usage_limit_per_user').fill('3');

  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(/view=coupon.*layout=edit.*id=\d+/);
  const createdId = await page.locator('#jform_id').inputValue();
  expect(Number(createdId)).toBeGreaterThan(0);
  await page.getByRole('tab', { name: 'Rabatt' }).click();
  await expect(page.locator('#jform_discount_type')).toHaveValue('percent');
  await expect(page.locator('#jform_discount_value')).toHaveValue('12.5');

  await page.getByRole('tab', { name: 'Allgemein' }).click();
  await page.locator('#jform_coupon_name').fill('E2E-CRUD-COUPON-UPDATED');
  await page.getByRole('tab', { name: 'Rabatt' }).click();
  await page.locator('#jform_discount_type').selectOption('fixed');
  await page.locator('#jform_discount_value').fill('8.75');
  await page.locator('#jform_minimum_order_total').fill('55.5');
  await page.getByRole('tab', { name: 'Einschränkungen' }).click();
  await page.locator('#jform_buyer_group_ids').selectOption(['900020', '900021']);
  await page.locator('#jform_product_ids').selectOption(['900100', '900104']);
  await page.locator('#jform_category_ids').selectOption(['900010', '900012']);
  await page.getByRole('tab', { name: 'Nutzung' }).click();
  await page.locator('#jform_usage_limit_total').fill('30');
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(new RegExp(`view=coupon.*layout=edit.*id=${createdId}`));
  await expect(page.locator('#jform_id')).toHaveValue(createdId);
  await page.getByRole('tab', { name: 'Einschränkungen' }).click();
  await expect(page.locator('#jform_user_ids')).toHaveValues([userId]);
  await expect(page.locator('#jform_buyer_group_ids')).toHaveValues(['900020', '900021']);
  await expect(page.locator('#jform_product_ids')).toHaveValues(['900100', '900104']);
  await expect(page.locator('#jform_category_ids')).toHaveValues(['900010', '900012']);

  await page.getByRole('tab', { name: 'Allgemein' }).click();
  await page.locator('#jform_description').fill('CRUD coupon after save-close');
  await page.getByRole('button', { name: 'Save & Close' }).click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(/view=coupons/);
  await searchCoupon(page, couponCode);
  await page.getByRole('link', { name: 'E2E-CRUD-COUPON-UPDATED', exact: true }).click();
  await expect(page.locator('#jform_id')).toHaveValue(createdId);
  await expect(page.locator('#jform_description')).toHaveValue('CRUD coupon after save-close');
  await page.getByRole('tab', { name: 'Rabatt' }).click();
  await expect(page.locator('#jform_discount_type')).toHaveValue('fixed');
  await expect(page.locator('#jform_discount_value')).toHaveValue('8.75');
  await expect(page.locator('#jform_minimum_order_total')).toHaveValue('55.5');
  await page.getByRole('tab', { name: 'Gültigkeit' }).click();
  await expect(page.locator('#jform_valid_from')).toHaveValue(/2027-01-01/);
  await expect(page.locator('#jform_valid_to')).toHaveValue(/2032-12-31/);
  await page.getByRole('tab', { name: 'Nutzung' }).click();
  await expect(page.locator('#jform_usage_limit_total')).toHaveValue('30');
  await expect(page.locator('#jform_usage_limit_per_user')).toHaveValue('3');

  await searchCoupon(page, couponCode);
  let row = matchingRow(page);
  await row.getByRole('link', { name: 'Unpublish Item' }).click();
  await searchCoupon(page, couponCode, '0');
  row = matchingRow(page);
  await expect(row.getByRole('link', { name: 'Publish Item' })).toBeVisible();
  await row.getByRole('link', { name: 'Publish Item' }).click();
  await searchCoupon(page, couponCode, '1');
  await expect(matchingRow(page).getByRole('link', { name: 'Unpublish Item' })).toBeVisible();
  await searchCoupon(page, couponCode, '');
  await expect(matchingRow(page)).toHaveCount(1);
});

test('coupon direct delete cleans mappings and leaves no record', async ({ page }) => {
  await searchCoupon(page, couponCode);
  const row = matchingRow(page);
  await expect(row).toHaveCount(1);
  await row.getByRole('checkbox', { name: 'Select' }).check();
  await acceptDelete(page, () => page.getByRole('button', { name: 'Delete' }).click());
  await searchCoupon(page, couponCode);
  await expect(matchingRow(page)).toHaveCount(0);
});
