const { test, expect } = require('@playwright/test');
const { authenticateAdministrator, installDiagnostics, openView } = require('../support/browser');

test('Joomla and authenticated FDShop administrator foundation', async ({ page, context, baseURL }, testInfo) => {
  const diagnostics = await installDiagnostics(page, baseURL);

  const frontendResponse = await page.goto('/');
  expect(frontendResponse?.status(), 'Joomla frontend HTTP status').toBe(200);

  await authenticateAdministrator(page, context, testInfo);
  await expect(page).toHaveURL(/\/administrator\/(index\.php)?/);

  await openView(page, 'dashboard');
  await expect(page.locator('body')).toContainText(/FDShop/i);

  if (process.env.FDSHOP_BROWSER_INJECT_FAILURE === 'missing-element') {
    await expect(page.getByTestId('fdshop-controlled-missing-element')).toBeVisible({ timeout: 1_000 });
  } else if (process.env.FDSHOP_BROWSER_INJECT_FAILURE) {
    throw new Error('Unknown browser failure injection');
  }

  diagnostics.expectClean();
});
