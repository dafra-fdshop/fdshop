const { test, expect } = require('@playwright/test');
const { authenticateAdministrator, authenticateSiteUser, installDiagnostics, openView } = require('../support/browser');

test('guest is sent to Joomla login and the authenticated account is responsive and order-owned', async ({ page, context, baseURL }, testInfo) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  const guest = await page.goto('/index.php?option=com_fdshop&view=account');
  expect(guest?.status()).toBe(200);
  await expect(page).toHaveURL(/(?:option=com_users.*view=login|component\/users\/login)/);

  await authenticateSiteUser(page);
  const response = await page.goto('/index.php?option=com_fdshop&view=account');
  expect(response?.status()).toBe(200);
  await expect(page.getByRole('heading', { name: 'Mein Konto', exact: true })).toBeVisible();
  await expect(page.getByRole('link', { name: /Meine Bestellungen/ })).toBeVisible();
  await expect(page.getByText('E2E-ORDER-BUNDLE')).toBeVisible();

  await page.getByRole('link', { name: 'Persönliche Daten', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Persönliche Daten' })).toBeVisible();
  await expect(page.getByLabel(/Vorname/)).toBeVisible();
  await expect(page.getByLabel(/Nachname/)).toBeVisible();

  await page.goto('/index.php?option=com_fdshop&view=account&section=orders&order_id=900801');
  await expect(page.getByRole('heading', { name: /Bestellung E2E-ORDER-BUNDLE/ })).toBeVisible();
  await expect(page.getByText('E2E Bundle Aktiv')).toBeVisible();
  await expect(page.getByText('71,02 EUR').first()).toBeVisible();

  await page.setViewportSize({ width: 390, height: 844 });
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true);
  await expect(page.getByRole('button', { name: /Abholstation ändern/ })).toBeVisible();
  diagnostics.expectClean();
});

test('account configuration and withdrawal column are visible to administrators', async ({ page, context, baseURL }, testInfo) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await authenticateAdministrator(page, context, testInfo);
  await openView(page, 'configuration');
  await page.getByRole('tab', { name: 'User-Bereich' }).click();
  await expect(page.getByLabel(/Hinweisfrist für Widerruf/)).toHaveValue('14');
  await expect(page.getByLabel(/Maximale Dateigröße/)).toHaveValue('8');

  await openView(page, 'orders');
  await expect(page.getByRole('columnheader', { name: /Widerruf/ })).toBeVisible();
  await expect(page.getByRole('columnheader', { name: /Datum.*bestellt.*geändert/ })).toBeVisible();
  diagnostics.expectClean();
});
