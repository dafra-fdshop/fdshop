const { test, expect } = require('@playwright/test');
const { authenticateSiteUser, installDiagnostics } = require('../support/browser');

test('checkout accepts an empty disabled former required profile field', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await authenticateSiteUser(page);
  await page.goto('/warenkorb');
  const cart = page.locator('[data-fdshop-cart]');
  await cart.locator('[data-cart-terms]').check();
  await cart.getByRole('button', { name: 'Zahlungspflichtig bestellen' }).click();
  await expect(page).toHaveURL(/view=checkoutconfirmation&order_number=/);
  await expect(page.locator('[data-customer-snapshot]')).not.toContainText('Teststraße 12');
  diagnostics.expectClean();
});
