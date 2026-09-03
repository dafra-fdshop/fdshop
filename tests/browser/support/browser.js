const fs = require('node:fs');
const { expect } = require('@playwright/test');

async function installDiagnostics(page, baseURL) {
  const sandboxOrigin = new URL(baseURL).origin;
  const errors = [];
  page.on('console', message => {
    if (message.type() === 'error') errors.push(`console.error: ${message.text()}`);
  });
  page.on('pageerror', error => errors.push(`pageerror: ${error.message}`));
  page.on('requestfailed', request => {
    const failure = request.failure();
    errors.push(`requestfailed: ${request.method()} ${request.url()} (${failure?.errorText || 'unknown'})`);
  });
  page.on('response', response => {
    if (response.status() >= 400) errors.push(`http ${response.status()}: ${response.request().method()} ${response.url()}`);
  });
  await page.route('**/*', async route => {
    const requestUrl = new URL(route.request().url());
    if (requestUrl.origin !== sandboxOrigin && !['data:', 'blob:'].includes(requestUrl.protocol)) {
      errors.push(`external request blocked: ${route.request().url()}`);
      await route.abort('blockedbyclient');
      return;
    }
    await route.continue();
  });
  return {
    expectClean() {
      expect(errors, `serious browser diagnostics:\n${errors.join('\n')}`).toEqual([]);
    },
  };
}

async function authenticateAdministrator(page, context, testInfo) {
  const username = process.env.JOOMLA_ADMIN_USERNAME;
  const password = process.env.JOOMLA_ADMIN_PASSWORD;
  expect(username, 'local Joomla administrator username must be provided').toBeTruthy();
  expect(password, 'local Joomla administrator password must be provided').toBeTruthy();
  const response = await page.goto('/administrator/');
  expect(response?.status(), 'Joomla administrator HTTP status').toBe(200);
  await expect(page.locator('#mod-login-username')).toBeVisible();
  await expect(page.locator('#mod-login-password')).toBeVisible();
  await expect(page.locator('#btn-login-submit')).toBeVisible();
  await page.locator('#mod-login-username').fill(username);
  await page.locator('#mod-login-password').fill(password);
  await page.locator('#btn-login-submit').click();
  await page.waitForLoadState('networkidle');
  await expect(page.locator('#mod-login-username')).toHaveCount(0);
  const hideTourButton = page.getByRole('button', { name: 'Hide Forever' });
  const tourIsVisible = await hideTourButton.waitFor({ state: 'visible', timeout: 3_000 })
    .then(() => true).catch(() => false);
  if (tourIsVisible) {
    await hideTourButton.click();
    await expect(hideTourButton).toBeHidden();
  }
  const statePath = testInfo.outputPath('auth-state.json');
  fs.mkdirSync(testInfo.outputDir, { recursive: true });
  await context.storageState({ path: statePath });
}

async function openView(page, view) {
  const response = await page.goto(`/administrator/index.php?option=com_fdshop&view=${view}`);
  expect(response?.status(), `${view} HTTP status`).toBe(200);
  await expect(page).toHaveURL(new RegExp(`option=com_fdshop.*view=${view}`));
  await expect(page.locator('#mod-login-username')).toHaveCount(0);
}

module.exports = { authenticateAdministrator, installDiagnostics, openView };
