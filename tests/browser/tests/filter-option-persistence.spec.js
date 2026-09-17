const { test, expect } = require('@playwright/test');
const { authenticateAdministrator, installDiagnostics } = require('../support/browser');

test.setTimeout(120_000);

test('inactive product options are preserved but cannot be assigned anew', async ({ page, context, baseURL }, testInfo) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await authenticateAdministrator(page, context, testInfo);

  const straightId = process.env.FDSHOP_FILTER_STRAIGHT_ID;
  const fannedId = process.env.FDSHOP_FILTER_FANNED_ID;
  const romanCandleId = process.env.FDSHOP_FILTER_ROMAN_CANDLE_ID;
  expect(straightId).toBeTruthy();
  expect(fannedId).toBeTruthy();
  expect(romanCandleId).toBeTruthy();

  const openProduct = async () => {
    await page.goto('/administrator/index.php?option=com_fdshop&view=product&layout=edit&id=900104');
    await expect(page.locator('#jform_id')).toHaveValue('900104');
    await page.getByRole('tab', { name: 'Besondere Felder' }).click();
  };
  const save = async () => {
    await page.getByRole('button', { name: 'Save', exact: true }).click();
    await page.waitForLoadState('networkidle');
    await page.getByRole('tab', { name: 'Besondere Felder' }).click();
  };

  await openProduct();
  const straight = page.locator(`input[name="jform[filter_option_ids][]"][value="${straightId}"]`);
  const romanCandle = page.locator(`input[name="jform[filter_option_ids][]"][value="${romanCandleId}"]`);
  await expect(straight).toBeChecked();
  await expect(straight.locator('xpath=following-sibling::label')).toContainText('(inaktiv)');

  await romanCandle.check();
  await save();
  await expect(straight).toBeChecked();
  await expect(romanCandle).toBeChecked();

  await page.locator('form#adminForm').evaluate((form, optionId) => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'jform[filter_option_ids][]';
    input.value = optionId;
    form.appendChild(input);
  }, fannedId);
  await save();
  await expect(page.locator(`input[name="jform[filter_option_ids][]"][value="${fannedId}"]`)).toHaveCount(0);
  await expect(straight).toBeChecked();

  await straight.uncheck();
  await romanCandle.uncheck();
  await save();
  await expect(page.locator(`input[name="jform[filter_option_ids][]"][value="${straightId}"]`)).toHaveCount(0);
  await expect(romanCandle).not.toBeChecked();
  diagnostics.expectClean();
});
