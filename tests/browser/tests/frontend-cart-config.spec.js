const { test, expect } = require('@playwright/test');
const { installDiagnostics } = require('../support/browser');

test('terms checkbox is hidden when disabled by FDShop configuration', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  const response = await page.goto('/warenkorb');
  expect(response?.status()).toBe(200);
  await expect(page.locator('[data-cart-terms]')).toHaveCount(0);
  diagnostics.expectClean();
});
