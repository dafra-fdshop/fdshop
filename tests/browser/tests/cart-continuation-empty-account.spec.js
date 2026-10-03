const { test, expect } = require('@playwright/test');
const { installDiagnostics } = require('../support/browser');

test('checkout login transfers a guest cart into an empty account and returns directly to cart', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.goto('/warenkorb');
  const cart = page.locator('[data-fdshop-cart]');
  const tokenName = await cart.locator('[data-cart-token] input').getAttribute('name');
  const added = await page.evaluate(async ({ tokenName }) => {
    const body = new FormData();
    body.append(tokenName, '1');
    body.append('product_id', '900100');
    body.append('quantity', '1');
    return (await fetch('index.php?option=com_fdshop&format=json&task=cart.add', { method: 'POST', body })).json();
  }, { tokenName });
  expect(added.success, added.message).toBe(true);
  await page.reload();
  await cart.getByRole('button', { name: 'Weiter zum Bestellen' }).click();
  const login = page.locator('.fdshop-account-entry__login form');
  await login.locator('input[name="username"]').fill(process.env.JOOMLA_ADMIN_USERNAME);
  await login.locator('input[name="password"]').fill(process.env.JOOMLA_ADMIN_PASSWORD);
  await login.getByRole('button', { name: /Log in|Anmelden/i }).click();

  await expect(page).toHaveURL(/(?:option=com_fdshop.*view=cart|warenkorb)/);
  await expect(page.locator('[data-cart-conflict]')).toHaveCount(0);
  await expect(page.locator('[data-cart-item]')).toHaveCount(1);
  await expect(page.locator('[data-cart-item]').first()).toContainText('E2E-PROD-ACTIVE');
  diagnostics.expectClean();
});
