const { test, expect } = require('@playwright/test');
const { installDiagnostics } = require('../support/browser');

test('configured customer fields control visibility, requirement and ordering', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.goto('/index.php?option=com_users&view=registration');
  const form = page.locator('#member-registration');
  await expect(form.getByLabel(/Street|Straße/i)).toHaveCount(0);
  await expect(form.getByLabel(/Company|Firma/i)).toHaveAttribute('required', '');
  await expect(form.getByLabel(/First name|Vorname/i)).toHaveAttribute('required', '');
  await expect(form.getByLabel(/Last name|Nachname/i)).toHaveAttribute('required', '');
  const ids = await form.locator('input[id^="jform_fdshop_customer_"]').evaluateAll(nodes => nodes.map(node => node.id));
  expect(ids.indexOf('jform_fdshop_customer_phone')).toBeLessThan(ids.indexOf('jform_fdshop_customer_first_name'));
  diagnostics.expectClean();
});
