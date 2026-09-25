const { test, expect } = require('@playwright/test');
const { authenticateSiteUser, installDiagnostics } = require('../support/browser');

test('checkout blocks a legacy user with incomplete required customer data', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await authenticateSiteUser(page);
  await page.goto('/warenkorb');
  const cart = page.locator('[data-fdshop-cart]');
  await cart.locator('[data-cart-terms]').check();
  await cart.getByRole('button', { name: 'Zahlungspflichtig bestellen' }).click();
  await expect(cart.locator('[data-fdshop-cart-message]')).toContainText('vollständigen Kundendaten');
  await expect(page).toHaveURL(/warenkorb/);
  diagnostics.expectClean();
});
