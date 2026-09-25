const { test, expect } = require('@playwright/test');
const { installDiagnostics } = require('../support/browser');

test('Joomla registration and profile persist FDShop customer data without a third visible name field', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  const response = await page.goto('/index.php?option=com_users&view=registration');
  expect(response?.status()).toBe(200);
  const form = page.locator('#member-registration');
  await expect(form.locator('input[name="jform[name]"]')).toBeHidden();
  await form.getByLabel('Vorname').fill('Max');
  await form.getByLabel('Nachname').fill('Beispiel');
  await form.getByLabel('Firma').fill('Beispielhandel GmbH');
  await form.getByLabel('Straße + Hausnummer').fill('Musterweg 7');
  await form.getByLabel('PLZ').fill('54321');
  await form.getByLabel('Ort').fill('Beispielstadt');
  await expect(form.getByLabel('Land')).toHaveValue('Deutschland');
  await form.getByLabel('Telefon').fill('+49 40 123456');
  await form.locator('#jform_username').fill('fdshop_customer_e2e');
  await form.locator('#jform_password1').fill('FDShop-Test-2026!');
  await form.locator('#jform_password2').fill('FDShop-Test-2026!');
  await form.locator('#jform_email1').fill('fdshop-customer-e2e@example.test');
  await form.getByRole('button', { name: /Registrieren|Register/i }).click();
  await expect(page.locator('input[name="jform[username]"]')).toHaveCount(0);

  await page.goto('/index.php?option=com_users&view=login');
  await page.locator('#username').fill('fdshop_customer_e2e');
  await page.locator('#password').fill('FDShop-Test-2026!');
  await page.locator('form').filter({ has: page.locator('#username') }).getByRole('button', { name: /Log in|Anmelden/i }).click();
  await expect(page.locator('#username')).toHaveCount(0);

  await page.goto('/index.php?option=com_users&view=profile&layout=edit');
  await expect(page.getByLabel('Vorname')).toHaveValue('Max');
  await expect(page.getByLabel('Nachname')).toHaveValue('Beispiel');
  await expect(page.getByLabel('Ort')).toHaveValue('Beispielstadt');
  await page.getByLabel('Telefon').fill('+49 40 654321');
  await page.locator('form').getByRole('button', { name: /Speichern|Save/i }).click();
  await page.goto('/index.php?option=com_users&view=profile&layout=edit');
  await expect(page.getByLabel('Telefon')).toHaveValue('+49 40 654321');
  diagnostics.expectClean();
});
