const { test, expect } = require('@playwright/test');
const { authenticateSiteUser, installDiagnostics } = require('../support/browser');

test('guest cart is isolated by session and cannot create an order', async ({ page, browser, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.goto('/warenkorb');
  const cart = page.locator('[data-fdshop-cart]');
  await expect(cart.locator('[data-cart-item]')).toHaveCount(0);
  const tokenName = await cart.locator('[data-cart-token] input').getAttribute('name');
  const payload = await page.evaluate(async ({ tokenName }) => {
    const body = new FormData(); body.append(tokenName, '1'); body.append('product_id', '900100'); body.append('quantity', '1');
    return (await fetch('index.php?option=com_fdshop&format=json&task=cart.add', { method: 'POST', body })).json();
  }, { tokenName });
  expect(payload.success, payload.message).toBe(true);
  await page.reload();
  await expect(cart.locator('[data-cart-item]')).toHaveCount(1);
  const item = cart.locator('[data-cart-item]').first();
  await item.getByRole('button', { name: 'Menge erhöhen' }).click();
  await item.getByRole('button', { name: 'Menge aktualisieren' }).click();
  await expect(item.locator('[data-cart-quantity]')).toHaveValue('2');
  await cart.getByRole('button', { name: 'ändern' }).first().click();
  await cart.locator('[data-cart-select-shipment="900601"]').click();
  await cart.getByRole('button', { name: 'ändern' }).nth(1).click();
  await cart.locator('[data-cart-select-payment="900611"]').click();
  await expect(cart.locator('[data-cart-total]')).toHaveText('52,47 €');

  const otherContext = await browser.newContext({ baseURL });
  const otherPage = await otherContext.newPage();
  await otherPage.goto('/warenkorb');
  await expect(otherPage.locator('[data-cart-item]')).toHaveCount(0);
  await otherContext.close();

  await cart.getByRole('button', { name: 'Zahlungspflichtig bestellen' }).click();
  await expect(cart.locator('[data-fdshop-cart-message]')).toContainText('an oder registrieren');
  await item.getByRole('button', { name: /entfernen/ }).click();
  await expect(cart.locator('[data-cart-item]')).toHaveCount(0);
  const replacement = await page.evaluate(async ({ tokenName }) => {
    const body = new FormData(); body.append(tokenName, '1'); body.append('product_id', '900100'); body.append('quantity', '1');
    return (await fetch('index.php?option=com_fdshop&format=json&task=cart.add', { method: 'POST', body })).json();
  }, { tokenName });
  expect(replacement.success, replacement.message).toBe(true);
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
  await expect(cart.locator('[data-cart-terms]')).toHaveAttribute('data-required', '1');
  await expect(cart.locator('[data-cart-remark]')).toBeVisible();
  await expect(cart.getByRole('button', { name: /Bemerkung speichern/i })).toHaveCount(0);
  await expect(normal.getByRole('button', { name: 'Menge aktualisieren' })).toHaveAttribute('title', 'Menge aktualisieren');
  await expect(cart.locator('.fdshop-cart__table-head > span').first()).toHaveCSS('white-space', 'nowrap');
  const headings = await cart.locator('.fdshop-cart__table-head > span').evaluateAll(elements => elements.map(element => ({ left: element.getBoundingClientRect().left, right: element.getBoundingClientRect().right })));
  expect(headings[2].left).toBeGreaterThan(headings[1].left);
  expect(headings[3].left).toBeGreaterThan(headings[2].left);
  expect(headings[4].left).toBeGreaterThan(headings[3].left);

  await cart.getByLabel('Gutscheincode').fill('E2E-EXPIRED');
  await cart.getByRole('button', { name: 'Übernehmen' }).click();
  await expect(cart.locator('[data-fdshop-cart-message]')).toContainText('abgelaufen');
  await cart.getByLabel('Gutscheincode').fill('e2e-percent');
  await cart.getByRole('button', { name: 'Übernehmen' }).click();
  await expect(cart.locator('[data-cart-coupon-discount]')).toHaveText('12,70 €');
  await expect(cart.locator('[data-cart-total]')).toHaveText('119,26 €');
  await expect(cart.getByLabel('Gutscheincode')).toHaveValue('E2E-PERCENT');
  await cart.locator('[data-cart-remark]').fill('Bitte sicher verpacken.');

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
  await normal.getByRole('button', { name: 'Menge aktualisieren' }).click();
  const firstUpdatePayload = await (await firstUpdateResponse).json();
  expect(firstUpdatePayload.success, firstUpdatePayload.message).toBe(true);
  expect(firstUpdatePayload.data.items.find(item => item.id === 910000)).toMatchObject({ quantity: 2, lineTotal: '39,98 €' });
  await expect(cart.locator('[data-fdshop-cart-message]')).toContainText('Menge wurde aktualisiert');
  await expect(normal.locator('[data-cart-quantity]')).toHaveValue('2');
  await expect(normal.locator('[data-cart-line-total]')).toContainText('39,98 €');
  await expect(cart.locator('[data-cart-remark]')).toHaveValue('Bitte sicher verpacken.');
  await normal.getByRole('button', { name: 'Menge reduzieren' }).click();
  await normal.getByRole('button', { name: 'Menge aktualisieren' }).click();
  await expect(normal.locator('[data-cart-quantity]')).toHaveValue('1');

  await normal.locator('[data-cart-quantity]').fill('3');
  await normal.getByRole('button', { name: 'Menge aktualisieren' }).click();
  await expect(normal.locator('[data-cart-quantity]')).toHaveValue('3');
  await expect(cart.locator('[data-cart-total]')).toHaveText('155,24 €');
  expect(documentRequests).toBe(0);

  await normal.locator('[data-cart-quantity]').fill('0');
  await normal.getByRole('button', { name: 'Menge aktualisieren' }).click();
  await expect(cart.locator('[data-fdshop-cart-message]')).toContainText('Mindestbestellmenge');
  await expect(normal.locator('[data-cart-quantity]')).toHaveValue('3');
  await normal.locator('[data-cart-quantity]').fill('-1');
  await normal.getByRole('button', { name: 'Menge aktualisieren' }).click();
  await expect(normal.locator('[data-cart-quantity]')).toHaveValue('3');
  await normal.locator('[data-cart-quantity]').fill('11');
  await normal.getByRole('button', { name: 'Menge aktualisieren' }).click();
  await expect(normal.locator('[data-cart-quantity]')).toHaveValue('11');
  await normal.locator('[data-cart-quantity]').fill('21');
  await normal.getByRole('button', { name: 'Menge aktualisieren' }).click();
  await expect(cart.locator('[data-fdshop-cart-message]')).toContainText('Gewünschte Menge nicht verfügbar');
  await expect(normal.locator('[data-cart-quantity]')).toHaveValue('11');
  await normal.locator('[data-cart-quantity]').fill('3');
  await normal.getByRole('button', { name: 'Menge aktualisieren' }).click();

  await discount.locator('[data-cart-quantity]').fill('1');
  await discount.getByRole('button', { name: 'Menge aktualisieren' }).click();
  await expect(cart.locator('[data-fdshop-cart-message]')).toContainText('Mindestbestellmenge');
  await expect(discount.locator('[data-cart-quantity]')).toHaveValue('2');
  await discount.locator('[data-cart-quantity]').fill('10');
  await discount.getByRole('button', { name: 'Menge aktualisieren' }).click();
  await expect(cart.locator('[data-fdshop-cart-message]')).toContainText('Höchstbestellmenge');
  await expect(discount.locator('[data-cart-quantity]')).toHaveValue('2');
  await discount.locator('[data-cart-quantity]').fill('3');
  await discount.getByRole('button', { name: 'Menge aktualisieren' }).click();
  await expect(cart.locator('[data-fdshop-cart-message]')).toContainText('Bestellschrittweite');
  await expect(discount.locator('[data-cart-quantity]')).toHaveValue('2');
  await lowStock.locator('[data-cart-quantity]').fill('3');
  await lowStock.getByRole('button', { name: 'Menge aktualisieren' }).click();
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
  await expect(cart.locator('[data-cart-total]')).toHaveText('162,74 €');

  await cart.locator('[data-cart-terms]').check();
  await page.reload();
  await expect(cart.locator('[data-cart-shipment-name]')).toHaveText('E2E Versand Inaktiv');
  await expect(cart.locator('[data-cart-payment-name]')).toHaveText('E2E Zahlung Inaktiv');
  await expect(cart.locator('[data-cart-terms]')).not.toBeChecked();

  await expect(cart.getByLabel('Gutscheincode')).toBeEnabled();
  await expect(cart.getByRole('button', { name: 'Übernehmen' })).toBeEnabled();
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
  await expect(cart.locator('[data-cart-coupon-discount]')).toHaveText('0,00 €');
  diagnostics.expectClean();
});
