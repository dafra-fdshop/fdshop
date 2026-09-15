const { test, expect } = require('@playwright/test');
const { authenticateSiteUser, installDiagnostics } = require('../support/browser');

test('configurable bundle validates, calculates, snapshots and removes atomically', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.goto('/index.php?option=com_fdshop&view=product&id=900100');
  await page.getByRole('button', { name: 'Bundle zusammenstellen' }).click();
  const dialog = page.locator('[data-fdshop-bundle-dialog]');
  await expect(dialog).toBeVisible();
  await expect(dialog.getByRole('heading', { name: 'E2E Bundle Aktiv' })).toBeVisible();
  await expect(dialog.locator('[data-bundle-quantity]')).toHaveCount(2);
  await expect(dialog.getByRole('button', { name: 'Speichern' })).toBeDisabled();

  const quantities = dialog.locator('[data-bundle-quantity]');
  await quantities.nth(0).fill('1');
  await quantities.nth(0).dispatchEvent('change');
  await expect(dialog.locator('[data-bundle-message]')).toContainText('mindestens zwei');
  await quantities.nth(1).fill('1');
  await quantities.nth(1).dispatchEvent('change');
  await expect(dialog.locator('[data-bundle-distinct]')).toHaveText('2');
  await expect(dialog.locator('[data-bundle-count]')).toHaveText('2');
  await expect(dialog.locator('[data-bundle-subtotal]')).toHaveText('59,98 €');
  await expect(dialog.locator('[data-bundle-discount]')).toHaveText('-3,00 €');
  await expect(dialog.locator('[data-bundle-total]')).toHaveText('56,98 €');

  const addResponse = page.waitForResponse(response => response.url().includes('task=bundle.addToCart'));
  await dialog.getByRole('button', { name: 'In den Warenkorb' }).click();
  const addPayload = await (await addResponse).json();
  expect(addPayload.success, addPayload.message).toBe(true);
  await expect(page).toHaveURL(/view=cart|warenkorb/);
  const bundle = page.locator('[data-cart-bundle]');
  await expect(bundle).toHaveCount(1);
  await expect(bundle).toContainText('E2E Bundle Aktiv');
  await expect(bundle).toContainText('56,98 €');
  await expect(bundle.locator('li')).toHaveCount(2);
  await expect(page.locator('[data-cart-subtotal]')).toHaveText('56,98 €');

  await bundle.getByRole('button', { name: 'Bundle entfernen' }).click();
  await expect(page.locator('[data-cart-bundle]')).toHaveCount(0);
  diagnostics.expectClean();
});

test('bundle endpoint rejects excess per-product quantity without partial cart write', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.goto('/index.php?option=com_fdshop&view=product&id=900100');
  const tokenName = await page.locator('[data-fdshop-bundle-token] input').getAttribute('name');
  const result = await page.evaluate(async ({ tokenName }) => {
    const body = new FormData(); body.append(tokenName, '1'); body.append('bundle_id', '900400'); body.append('items', JSON.stringify({900100: 3, 900105: 1}));
    return (await fetch('index.php?option=com_fdshop&format=json&task=bundle.addToCart', {method: 'POST', body})).json();
  }, { tokenName });
  expect(result.success).toBe(false);
  expect(result.message).toContain('maximale Anzahl');
  await page.goto('/warenkorb');
  await expect(page.locator('[data-cart-bundle]')).toHaveCount(0);
  diagnostics.expectClean();
});

test('registered customer can save, load and delete a personal composition', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await authenticateSiteUser(page);
  await page.goto('/index.php?option=com_fdshop&view=product&id=900100');
  await page.getByRole('button', { name: 'Bundle zusammenstellen' }).click();
  const dialog = page.locator('[data-fdshop-bundle-dialog]');
  const quantities = dialog.locator('[data-bundle-quantity]');
  await quantities.nth(0).fill('1'); await quantities.nth(0).dispatchEvent('change');
  await quantities.nth(1).fill('1'); await quantities.nth(1).dispatchEvent('change');
  await expect(dialog.getByRole('button', { name: 'Speichern' })).toBeEnabled();
  page.once('dialog', prompt => prompt.accept('Mein E2E Bundle'));
  await dialog.getByRole('button', { name: 'Speichern' }).click();
  await expect(dialog.locator('[data-bundle-message]')).toContainText('gespeichert');
  await dialog.getByRole('button', { name: 'Bundle-Konfigurator schließen' }).click();
  await page.getByRole('button', { name: 'Bundle zusammenstellen' }).click();
  await expect(dialog.locator('.fdshop-bundle__saved')).toContainText('Mein E2E Bundle');
  await dialog.locator('.fdshop-bundle__saved').getByRole('button', { name: 'Laden' }).click();
  await expect(dialog.locator('[data-bundle-quantity]').nth(1)).toHaveValue('1');
  await dialog.locator('.fdshop-bundle__saved').getByRole('button', { name: 'Löschen' }).click();
  await expect(dialog.locator('.fdshop-bundle__saved')).toHaveCount(0);
  diagnostics.expectClean();
});
