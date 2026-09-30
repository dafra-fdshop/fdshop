const { test, expect } = require('@playwright/test');
const { authenticateSiteUser, installDiagnostics } = require('../support/browser');

async function openBuilder(page) {
  await page.goto('/index.php?option=com_fdshop&view=product&id=900100');
  await page.getByRole('button', { name: 'Bundle zusammenstellen' }).click();
  const dialog = page.locator('[data-fdshop-bundle-dialog]');
  await expect(dialog.getByRole('heading', { name: 'E2E Bundle Aktiv' })).toBeVisible();
  return dialog;
}

test('bundle experience loads, supports quick view, button/drag selection and discount progress', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.route('https://www.youtube-nocookie.com/**', route => route.fulfill({ status: 200, contentType: 'text/html', body: '<!doctype html><title>Bundle video fixture</title>' }));
  await page.route(/task=bundle\.builder/, async route => {
    await new Promise(resolve => setTimeout(resolve, 220));
    const response = await route.fetch();
    const payload = await response.json();
    const result = payload.data || payload;
    result.products.find(product => Number(product.id) === 900100).video_url = 'https://www.youtube.com/watch?v=aqz-KE-bpKQ';
    await route.fulfill({ response, json: payload });
  });
  await page.goto('/index.php?option=com_fdshop&view=product&id=900100');
  await page.getByRole('button', { name: 'Bundle zusammenstellen' }).click();
  const dialog = page.locator('[data-fdshop-bundle-dialog]');
  await expect(dialog.locator('.fdshop-bundle__loader')).toBeAttached();
  await expect(dialog.getByRole('heading', { name: 'E2E Bundle Aktiv' })).toBeVisible();
  await expect(dialog.getByText('Stell dein Feuerwerk zusammen')).toBeVisible();
  await expect(dialog.locator('.fdshop-bundle__pool [data-bundle-quantity]')).toHaveCount(0);
  await expect(dialog.getByText('Maximal 2 Stück je Produkt')).toBeVisible();

  const activePoolCard = dialog.locator('[data-bundle-pool-product="900100"]');
  await activePoolCard.getByRole('button', { name: 'Details zu E2E Produkt Aktiv' }).click();
  const quick = dialog.locator('[data-bundle-quick]');
  await expect(quick).toBeVisible();
  await expect(quick).toContainText('E2E-PROD-ACTIVE');
  await expect(quick).toContainText('Künstlich');
  await quick.getByRole('button', { name: 'Video ansehen' }).click();
  await expect(quick.locator('iframe')).toHaveAttribute('src', 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ');
  await quick.getByRole('button', { name: 'Produktdetails schließen' }).click();
  await expect(activePoolCard.getByRole('button', { name: 'Details zu E2E Produkt Aktiv' })).toBeFocused();

  const discountPoolCard = dialog.locator('[data-bundle-pool-product="900105"]');
  await discountPoolCard.getByRole('button', { name: 'Details zu E2E Produkt Aktionspreis' }).click();
  await expect(quick.getByRole('button', { name: 'Video ansehen' })).toHaveCount(0);
  await quick.getByRole('button', { name: '+ Zum Bundle hinzufügen' }).click();
  await expect(dialog.locator('[data-bundle-chosen-product="900105"]')).toBeVisible();
  await dialog.locator('[data-bundle-chosen-product="900105"]').getByRole('button', { name: 'Entfernen' }).click();

  await activePoolCard.getByRole('button', { name: '+ Hinzufügen' }).click();
  await expect(dialog.locator('[data-bundle-chosen-product="900100"]')).toBeVisible();
  await expect(dialog.locator('[data-bundle-message]')).toContainText('Noch ein Produkt');
  await dialog.locator('[data-bundle-pool-product="900105"]').dragTo(dialog.locator('[data-bundle-dropzone]'));
  await expect(dialog.locator('.fdshop-bundle__chosen-card')).toHaveCount(2);
  await expect(dialog.locator('[data-bundle-message]')).toContainText('Bundle ist bereit');
  await expect(dialog.locator('[data-bundle-progress-status]')).toContainText('5 % Rabatt aktiv');
  await expect(dialog.locator('[data-bundle-celebration]')).toContainText('5 % RABATT FREIGESCHALTET');
  await expect(dialog.locator('[data-bundle-subtotal]')).toHaveText('59,98 €');
  await expect(dialog.locator('[data-bundle-discount]')).toHaveText('−3,00 €');
  await expect(dialog.locator('[data-bundle-total]')).toHaveText('56,98 €');
  await expect(dialog.locator('[data-bundle-saving-row]')).toBeVisible();

  const discount = dialog.locator('[data-bundle-chosen-product="900105"]');
  await discount.getByRole('button', { name: 'Entfernen' }).click();
  await expect(dialog.locator('[data-bundle-celebration]')).toBeHidden();
  await discountPoolCard.getByRole('button', { name: '+ Hinzufügen' }).click();
  await expect(dialog.locator('[data-bundle-celebration]')).toBeHidden();

  const first = dialog.locator('[data-bundle-chosen-product="900100"]');
  await first.getByRole('button', { name: /erhöhen/ }).click();
  await expect(first.locator('[data-bundle-quantity]')).toHaveValue('2');
  await expect(first.getByRole('button', { name: /erhöhen/ })).toBeDisabled();
  await dialog.locator('[data-bundle-chosen-product="900105"]').getByRole('button', { name: /erhöhen/ }).click();
  await expect(dialog.locator('[data-bundle-progress-status]')).toContainText('10 % Rabatt aktiv');
  await expect(dialog.locator('[data-bundle-celebration]')).toContainText('10 % RABATT FREIGESCHALTET');
  await dialog.locator('[data-bundle-chosen-product="900105"]').getByRole('button', { name: /reduzieren/ }).click();
  await first.getByRole('button', { name: /reduzieren/ }).click();
  await first.getByRole('button', { name: 'Entfernen' }).click();
  await expect(dialog.locator('[data-bundle-chosen-product="900100"]')).toHaveCount(0);
  await dialog.getByRole('button', { name: 'Auswahl zurücksetzen' }).click();
  await expect(dialog.getByText('Stell dein Feuerwerk zusammen')).toBeVisible();
  await expect(dialog.getByRole('button', { name: 'In den Warenkorb' })).toBeDisabled();
  diagnostics.expectClean();
});

test('bundle cart request remains single and server snapshot totals stay authoritative', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  const dialog = await openBuilder(page);
  await dialog.locator('[data-bundle-pool-product="900100"] [data-bundle-add]').click();
  await dialog.locator('[data-bundle-pool-product="900105"] [data-bundle-add]').click();
  let addRequests = 0;
  page.on('request', request => { if (request.url().includes('task=bundle.addToCart')) addRequests += 1; });
  const addResponse = page.waitForResponse(response => response.url().includes('task=bundle.addToCart'));
  await dialog.getByRole('button', { name: 'In den Warenkorb' }).click();
  const payload = await (await addResponse).json();
  expect(payload.success, payload.message).toBe(true);
  await expect(page).toHaveURL(/view=cart|warenkorb/);
  expect(addRequests).toBe(1);
  const bundle = page.locator('[data-cart-bundle]');
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
  await page.goto('/index.php?option=com_fdshop&view=cart');
  await expect(page.locator('[data-cart-bundle]')).toHaveCount(0);
  diagnostics.expectClean();
});

test('registered customer can save, load and delete a composition', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await authenticateSiteUser(page);
  const dialog = await openBuilder(page);
  await dialog.locator('[data-bundle-pool-product="900100"] [data-bundle-add]').click();
  await dialog.locator('[data-bundle-pool-product="900105"] [data-bundle-add]').click();
  await expect(dialog.getByRole('button', { name: 'Bundle speichern' })).toBeEnabled();
  page.once('dialog', prompt => prompt.accept('Mein E2E Bundle'));
  await dialog.getByRole('button', { name: 'Bundle speichern' }).click();
  await expect(dialog.locator('[data-bundle-message]')).toContainText('gespeichert');
  await dialog.getByRole('button', { name: 'Bundle-Konfigurator schließen' }).click();
  await page.getByRole('button', { name: 'Bundle zusammenstellen' }).click();
  await expect(dialog.locator('.fdshop-bundle__saved')).toContainText('Mein E2E Bundle');
  await dialog.locator('.fdshop-bundle__saved').getByRole('button', { name: 'Laden' }).click();
  await expect(dialog.locator('[data-bundle-quantity]')).toHaveCount(2);
  await dialog.locator('.fdshop-bundle__saved').getByRole('button', { name: 'Löschen' }).click();
  await expect(dialog.locator('.fdshop-bundle__saved')).toHaveCount(0);
  diagnostics.expectClean();
});

test('mobile fallback stays usable and reduced motion suppresses bundle animation', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.setViewportSize({ width: 390, height: 844 });
  await page.emulateMedia({ reducedMotion: 'reduce' });
  const dialog = await openBuilder(page);
  await expect(dialog.locator('.fdshop-bundle__workspace')).toBeVisible();
  await dialog.locator('[data-bundle-pool-product="900100"] [data-bundle-add]').click();
  await expect(dialog.locator('[data-bundle-chosen-product="900100"]')).toBeVisible();
  expect(await page.evaluate(() => matchMedia('(prefers-reduced-motion: reduce)').matches)).toBe(true);
  await expect(dialog.locator('.fdshop-bundle__chosen-card')).toHaveCSS('animation-name', 'none');
  diagnostics.expectClean();
});
