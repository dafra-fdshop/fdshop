const { test, expect } = require('@playwright/test');
const { authenticateSiteUser, installDiagnostics } = require('../support/browser');

test('guest cannot use the database-backed cart', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  const response = await page.goto('/warenkorb');
  expect(response?.status()).toBe(200);
  await expect(page.locator('.fdshop-cart')).toContainText('Bitte melden Sie sich an');
  await expect(page.getByRole('button', { name: 'Zahlungspflichtig bestellen' })).toHaveCount(0);
  diagnostics.expectClean();
});

test('authenticated cart validates mutations and keeps checkout state correctly separated', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await authenticateSiteUser(page);
  const response = await page.goto('/warenkorb');
  expect(response?.status()).toBe(200);
  const cart = page.locator('[data-fdshop-cart]');
  await expect(cart.locator('[data-cart-item]')).toHaveCount(3);
  await expect(cart).not.toContainText('E2E-PROD-IMAGE');

  const normal = cart.locator('[data-cart-item="910000"]');
  const discount = cart.locator('[data-cart-item="910001"]');
  const lowStock = cart.locator('[data-cart-item="910002"]');
  await expect(normal).toContainText('E2E-PROD-ACTIVE');
  await expect(normal.locator('.fdshop-cart__product img')).toHaveAttribute('src', /product-placeholder\.svg$/);
  await expect(normal.locator('.fdshop-cart__product a')).toHaveAttribute('href', /\/product\/900100\?catid=900010$/);
  await expect(normal.locator('[data-cart-unit-price]')).toHaveText('19,99 €');
  await expect(normal.locator('[data-cart-line-total]')).toContainText('19,99 €');
  await expect(discount.locator('.fdshop-cart__regular-price')).toHaveText('50,00 €');
  await expect(discount.locator('[data-cart-unit-price]')).toHaveText('39,99 €');
  await expect(discount.locator('[data-cart-line-total]')).toContainText('79,98 €');
  await expect(cart.locator('[data-cart-subtotal]')).toHaveText('126,97 €');
  await expect(cart.locator('[data-cart-shipment-name]')).toHaveText('E2E Versand Standard');
  await expect(cart.locator('[data-cart-payment-name]')).toHaveText('E2E Zahlung Rechnung');
  await expect(cart.locator('[data-cart-shipment-fee]')).toHaveText('4,99 €');
  await expect(cart.locator('[data-cart-payment-fee]')).toHaveText('0,00 €');
  await expect(cart.locator('[data-cart-total]')).toHaveText('131,96 €');

  await page.setViewportSize({ width: 480, height: 900 });
  await expect(cart.locator('[data-cart-item="910000"]')).toBeVisible();
  await expect(cart.getByRole('button', { name: 'Menge erhöhen' }).first()).toBeVisible();
  await expect(cart.locator('.fdshop-cart__checkout')).toBeVisible();
  expect(await cart.locator('.fdshop-cart__grid').evaluate(element => getComputedStyle(element).gridTemplateColumns.split(' ').length)).toBe(1);
  await page.setViewportSize({ width: 1280, height: 720 });

  let documentRequests = 0;
  page.on('request', request => { if (request.resourceType() === 'document') documentRequests += 1; });
  await normal.getByRole('button', { name: 'Menge erhöhen' }).click();
  const firstUpdateResponse = page.waitForResponse(response => response.url().includes('task=cart.updateQuantity'));
  await normal.getByRole('button', { name: 'Aktualisieren' }).click();
  const firstUpdatePayload = await (await firstUpdateResponse).json();
  expect(firstUpdatePayload.success, firstUpdatePayload.message).toBe(true);
  expect(firstUpdatePayload.data.items.find(item => item.id === 910000)).toMatchObject({ quantity: 2, lineTotal: '39,98 €' });
  await expect(cart.locator('[data-fdshop-cart-message]')).toContainText('Menge wurde aktualisiert');
  await expect(normal.locator('[data-cart-quantity]')).toHaveValue('2');
  await expect(normal.locator('[data-cart-line-total]')).toContainText('39,98 €');
  await normal.getByRole('button', { name: 'Menge reduzieren' }).click();
  await normal.getByRole('button', { name: 'Aktualisieren' }).click();
  await expect(normal.locator('[data-cart-quantity]')).toHaveValue('1');

  await normal.locator('[data-cart-quantity]').fill('3');
  await normal.getByRole('button', { name: 'Aktualisieren' }).click();
  await expect(normal.locator('[data-cart-quantity]')).toHaveValue('3');
  await expect(cart.locator('[data-cart-total]')).toHaveText('171,94 €');
  expect(documentRequests).toBe(0);

  await normal.locator('[data-cart-quantity]').fill('0');
  await normal.getByRole('button', { name: 'Aktualisieren' }).click();
  await expect(cart.locator('[data-fdshop-cart-message]')).toContainText('Mindestbestellmenge');
  await expect(normal.locator('[data-cart-quantity]')).toHaveValue('3');
  await normal.locator('[data-cart-quantity]').fill('11');
  await normal.getByRole('button', { name: 'Aktualisieren' }).click();
  await expect(cart.locator('[data-fdshop-cart-message]')).toContainText('Höchstbestellmenge');
  await expect(normal.locator('[data-cart-quantity]')).toHaveValue('3');

  await discount.locator('[data-cart-quantity]').fill('3');
  await discount.getByRole('button', { name: 'Aktualisieren' }).click();
  await expect(cart.locator('[data-fdshop-cart-message]')).toContainText('Bestellschrittweite');
  await expect(discount.locator('[data-cart-quantity]')).toHaveValue('2');
  await lowStock.locator('[data-cart-quantity]').fill('3');
  await lowStock.getByRole('button', { name: 'Aktualisieren' }).click();
  await expect(cart.locator('[data-fdshop-cart-message]')).toContainText('Gewünschte Menge nicht verfügbar');
  await expect(lowStock.locator('[data-cart-quantity]')).toHaveValue('1');

  await cart.getByRole('button', { name: 'ändern' }).first().click();
  await cart.locator('[data-cart-select-shipment="900601"]').click();
  await expect(cart.locator('[data-cart-shipment-name]')).toHaveText('E2E Versand Inaktiv');
  await expect(cart.locator('[data-cart-shipment-fee]')).toHaveText('9,99 €');
  await cart.getByRole('button', { name: 'ändern' }).nth(1).click();
  await cart.locator('[data-cart-select-payment="900611"]').click();
  await expect(cart.locator('[data-cart-payment-name]')).toHaveText('E2E Zahlung Inaktiv');
  await expect(cart.locator('[data-cart-payment-fee]')).toHaveText('2,50 €');
  await expect(cart.locator('[data-cart-total]')).toHaveText('179,44 €');

  await cart.locator('[data-cart-remark]').fill('Bitte zur Abholung bereitstellen.');
  await cart.getByRole('button', { name: 'Bemerkung speichern' }).click();
  await expect(cart.locator('[data-fdshop-cart-message]')).toContainText('Bemerkung wurde gespeichert');
  await cart.locator('[data-cart-terms]').check();
  await page.reload();
  await expect(cart.locator('[data-cart-remark]')).toHaveValue('Bitte zur Abholung bereitstellen.');
  await expect(cart.locator('[data-cart-shipment-name]')).toHaveText('E2E Versand Inaktiv');
  await expect(cart.locator('[data-cart-payment-name]')).toHaveText('E2E Zahlung Inaktiv');
  await expect(cart.locator('[data-cart-terms]')).not.toBeChecked();

  await expect(cart.getByLabel('Gutscheincode')).toBeDisabled();
  await expect(cart.getByRole('button', { name: 'Übernehmen' })).toBeDisabled();
  await cart.getByRole('button', { name: 'Zahlungspflichtig bestellen' }).click();
  await expect(cart.locator('[data-fdshop-cart-message]')).toContainText('folgenden Paket aktiviert');
  await expect(page).toHaveURL(/\/warenkorb/);

  for (const id of ['910000', '910001', '910002']) {
    await cart.locator(`[data-cart-item="${id}"] [data-cart-remove]`).click();
  }
  await expect(cart.locator('[data-cart-item]')).toHaveCount(0);
  await expect(cart.locator('[data-fdshop-cart-empty]')).toHaveText('Aktuell sind noch keine Produkte im Warenkorb.');
  await expect(cart.locator('[data-fdshop-cart-empty]')).toBeVisible();
  await expect(cart.locator('[data-cart-subtotal]')).toHaveText('0,00 €');
  await expect(cart.locator('[data-cart-total]')).toHaveText('12,49 €');
  diagnostics.expectClean();
});
