const fs = require('node:fs');
const { test, expect } = require('@playwright/test');

test('Joomla and authenticated FDShop administrator foundation', async ({ page, context, baseURL }, testInfo) => {
  const username = process.env.JOOMLA_ADMIN_USERNAME;
  const password = process.env.JOOMLA_ADMIN_PASSWORD;
  expect(username, 'local Joomla administrator username must be provided').toBeTruthy();
  expect(password, 'local Joomla administrator password must be provided').toBeTruthy();

  const sandboxOrigin = new URL(baseURL).origin;
  const browserErrors = [];

  page.on('console', (message) => {
    if (message.type() === 'error') browserErrors.push(`console.error: ${message.text()}`);
  });
  page.on('pageerror', (error) => browserErrors.push(`pageerror: ${error.message}`));
  page.on('requestfailed', (request) => {
    const failure = request.failure();
    browserErrors.push(`requestfailed: ${request.method()} ${request.url()} (${failure?.errorText || 'unknown'})`);
  });
  page.on('response', (response) => {
    if (response.status() >= 400) {
      browserErrors.push(`http ${response.status()}: ${response.request().method()} ${response.url()}`);
    }
  });
  await page.route('**/*', async (route) => {
    const requestUrl = new URL(route.request().url());
    if (requestUrl.origin !== sandboxOrigin && !['data:', 'blob:'].includes(requestUrl.protocol)) {
      browserErrors.push(`external request blocked: ${route.request().url()}`);
      await route.abort('blockedbyclient');
      return;
    }
    await route.continue();
  });

  const frontendResponse = await page.goto('/');
  expect(frontendResponse?.status(), 'Joomla frontend HTTP status').toBe(200);

  const administratorResponse = await page.goto('/administrator/');
  expect(administratorResponse?.status(), 'Joomla administrator HTTP status').toBe(200);
  await expect(page.locator('#mod-login-username')).toBeVisible();
  await expect(page.locator('#mod-login-password')).toBeVisible();
  await expect(page.locator('#btn-login-submit')).toBeVisible();

  await page.locator('#mod-login-username').fill(username);
  await page.locator('#mod-login-password').fill(password);
  await page.locator('#btn-login-submit').click();
  await page.waitForLoadState('networkidle');
  await expect(page.locator('#mod-login-username')).toHaveCount(0);
  await expect(page).toHaveURL(/\/administrator\/(index\.php)?/);

  const hideTourButton = page.getByRole('button', { name: 'Hide Forever' });
  const tourIsVisible = await hideTourButton.waitFor({ state: 'visible', timeout: 3_000 })
    .then(() => true)
    .catch(() => false);
  if (tourIsVisible) {
    await hideTourButton.click();
    await expect(hideTourButton).toBeHidden();
  }

  const statePath = testInfo.outputPath('auth-state.json');
  fs.mkdirSync(testInfo.outputDir, { recursive: true });
  await context.storageState({ path: statePath });

  const fdshopResponse = await page.goto('/administrator/index.php?option=com_fdshop&view=dashboard');
  expect(fdshopResponse?.status(), 'FDShop administrator HTTP status').toBe(200);
  await expect(page).toHaveURL(/option=com_fdshop.*view=dashboard/);
  await expect(page.locator('#mod-login-username')).toHaveCount(0);
  await expect(page.locator('body')).toContainText(/FDShop/i);

  if (process.env.FDSHOP_BROWSER_INJECT_FAILURE === 'missing-element') {
    await expect(page.getByTestId('fdshop-controlled-missing-element')).toBeVisible({ timeout: 1_000 });
  } else if (process.env.FDSHOP_BROWSER_INJECT_FAILURE) {
    throw new Error('Unknown browser failure injection');
  }

  expect(browserErrors, `serious browser diagnostics:\n${browserErrors.join('\n')}`).toEqual([]);
});
