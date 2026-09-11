const { test, expect } = require('@playwright/test');
const { authenticateSiteUser, installDiagnostics } = require('../support/browser');

async function openCategory(page) {
  const response = await page.goto('/batterien');
  expect(response?.status()).toBe(200);
}

test('guest purchase uses the central action, adds quantities and reports a stock limit', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await openCategory(page);
  const card = page.locator('[data-product-id="900100"]');
  const action = card.locator('[data-fdshop-purchase]');
  await expect(action).toBeVisible();
  await expect(action.locator('[data-purchase-quantity]')).toHaveValue('1');
  await expect(action).not.toHaveClass(/fdshop-purchase--/);
  await expect(action.locator('[data-purchase-submit]')).toHaveCSS('border-radius', '50%');
  const primaryBackground = await card.getByRole('link', { name: 'Details', exact: true }).evaluate(element => getComputedStyle(element).backgroundColor);
  await expect(action.locator('[data-purchase-submit]')).toHaveCSS('background-color', primaryBackground);
  await action.evaluate(element => element.style.setProperty('--warning', 'rgb(1, 2, 3)'));
  await expect(action.locator('[data-purchase-submit]')).not.toHaveCSS('background-color', 'rgb(1, 2, 3)');
  await action.hover();
  await expect(action.locator('[data-purchase-quantity]')).toHaveCSS('opacity', '1');
  expect(await action.evaluate(element => {
    const button = element.querySelector('[data-purchase-submit]').getBoundingClientRect();
    const quantity = element.querySelector('[data-purchase-quantity]').getBoundingClientRect();
    return quantity.left >= button.right - 1;
  })).toBe(true);

  const addResponse = page.waitForResponse(response => response.url().includes('task=cart.add'));
  await action.locator('[data-purchase-submit]').click();
  const first = await (await addResponse).json();
  expect(first.success, first.message).toBe(true);
  expect(first.data.purchase).toMatchObject({ productId: 900100, requestedQuantity: 1, effectiveQuantity: 1, resultingCartQuantity: 1, adjusted: false, unitPrice: '19,99 €', lineAmount: '19,99 €' });
  await expect(page.locator('[data-purchase-modal]')).toBeVisible();
  await expect(page.locator('[data-purchase-product]')).toHaveText('E2E Produkt Aktiv');
  await expect(page.locator('[data-purchase-effective]')).toHaveText('1');
  await page.getByRole('button', { name: 'Weiter einkaufen' }).click();
  await expect(page.locator('[data-purchase-modal]')).toBeHidden();

  await action.locator('[data-purchase-quantity]').fill('3');
  const repeatResponse = page.waitForResponse(response => response.url().includes('task=cart.add'));
  await action.locator('[data-purchase-submit]').click();
  const repeat = await (await repeatResponse).json();
  expect(repeat.data.purchase).toMatchObject({ effectiveQuantity: 3, resultingCartQuantity: 4, adjusted: false });
  await page.getByRole('button', { name: 'Weiter einkaufen' }).click();

  await action.locator('[data-purchase-quantity]').fill('10');
  const limitedResponse = page.waitForResponse(response => response.url().includes('task=cart.add'));
  await action.locator('[data-purchase-submit]').click();
  const limited = await (await limitedResponse).json();
  expect(limited.data.purchase).toMatchObject({ requestedQuantity: 10, effectiveQuantity: 6, resultingCartQuantity: 10, adjusted: true });
  await expect(page.locator('[data-purchase-title]')).toHaveText('Menge angepasst');
  await expect(page.locator('[data-purchase-message]')).toContainText('maximal verfügbare Menge von 6');
  const cartHref = await page.locator('[data-purchase-cart]').getAttribute('href');
  expect(cartHref).toMatch(/(?:view=cart|\/fdshop\/cart)/);
  await page.locator('[data-purchase-cart]').click();
  await expect(page.locator('[data-fdshop-cart] [data-cart-item]')).toHaveCount(1);
  await expect(page.locator('[data-cart-quantity]')).toHaveValue('10');
  diagnostics.expectClean();
});

test('purchase validates zero, minimum and step and uses the server discount price', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await openCategory(page);
  const action = page.locator('[data-product-id="900105"] [data-fdshop-purchase]');
  const card = page.locator('[data-product-id="900105"]');
  const price = card.locator('.fdshop-card__price');
  const regularPrice = card.locator('.fdshop-card__regular-price');
  await expect(action.locator('[data-purchase-quantity]')).toHaveAttribute('min', '2');
  await expect(action.locator('[data-purchase-quantity]')).toHaveAttribute('step', '2');
  await expect(action.locator('[data-purchase-quantity]')).toHaveAttribute('max', '8');
  await expect(price.locator('strong')).toHaveCSS('color', 'rgb(224, 167, 33)');
  await expect(regularPrice).toHaveCSS('color', 'rgb(220, 53, 69)');
  await expect(regularPrice).toHaveCSS('text-decoration-line', 'line-through');
  const closedSizes = await price.evaluate(element => ({ current: getComputedStyle(element).fontSize, regular: getComputedStyle(element.querySelector('.fdshop-card__regular-price')).fontSize }));
  await action.hover();
  await expect(action.locator('[data-purchase-quantity]')).toHaveCSS('opacity', '1');
  expect(await price.evaluate((element, before) => {
    const action = element.parentElement.querySelector('[data-fdshop-purchase]').getBoundingClientRect();
    const quantity = element.parentElement.querySelector('[data-purchase-quantity]').getBoundingClientRect();
    const priceBox = element.getBoundingClientRect();
    return {
      currentSmaller: parseFloat(getComputedStyle(element).fontSize) < parseFloat(before.current),
      regularSmaller: parseFloat(getComputedStyle(element.querySelector('.fdshop-card__regular-price')).fontSize) < parseFloat(before.regular),
      quantityVisible: quantity.width > 0,
      noOverlap: priceBox.right <= action.left + 1,
      contained: action.right <= element.parentElement.getBoundingClientRect().right + 1,
    };
  }, closedSizes)).toEqual({ currentSmaller: true, regularSmaller: true, quantityVisible: true, noOverlap: true, contained: true });

  for (const [quantity, message] of [['0', 'größer als 0'], ['1', 'Mindestbestellmenge'], ['3', 'Bestellschrittweite']]) {
    await action.locator('[data-purchase-quantity]').fill(quantity);
    await action.locator('[data-purchase-submit]').click();
    await expect(action.locator('[data-purchase-error]')).toContainText(message);
    await expect(page.locator('[data-purchase-modal]')).not.toBeVisible();
  }

  await action.locator('[data-purchase-quantity]').fill('8');
  await action.locator('[data-purchase-submit]').click();
  await expect(page.locator('[data-purchase-modal]')).toBeVisible();
  await expect(page.locator('[data-purchase-price]')).toHaveText('39,99 €');
  await expect(page.locator('[data-purchase-amount]')).toHaveText('319,92 €');
  diagnostics.expectClean();
});

test('category remains independent of product details and request validation stays server-side', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.goto('/batterien?limit=48');
  const card = page.locator('[data-product-id="900108"]');
  await expect(card).toBeVisible();
  await expect(card.locator('[data-fdshop-purchase]')).toBeVisible();
  const response = page.waitForResponse(candidate => candidate.url().includes('task=cart.add'));
  await card.locator('[data-purchase-submit]').click();
  const payload = await (await response).json();
  expect(payload.success).toBe(false);
  expect(payload.message).toContain('nicht verfügbar');
  await expect(card.locator('[data-purchase-error]')).toContainText('nicht verfügbar');
  diagnostics.expectClean();
});

test('touch opens first and adds on second tap; detail and authenticated purchase use the same component', async ({ browser, baseURL }) => {
  const context = await browser.newContext({ baseURL, hasTouch: true, viewport: { width: 480, height: 900 } });
  const page = await context.newPage();
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.route('https://i.ytimg.com/**', route => route.fulfill({ status: 200, contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg"/>' }));
  await openCategory(page);
  const action = page.locator('[data-product-id="900100"] [data-fdshop-purchase]');
  let addRequests = 0;
  page.on('request', request => { if (request.url().includes('task=cart.add')) addRequests += 1; });
  await action.locator('[data-purchase-submit]').tap();
  await expect(action).toHaveClass(/is-open/);
  expect(addRequests).toBe(0);
  await action.locator('[data-purchase-submit]').tap();
  await expect(page.locator('[data-purchase-modal]')).toBeVisible();
  expect(addRequests).toBe(1);
  await page.getByRole('button', { name: 'Weiter einkaufen' }).click();

  await page.goto('/index.php?option=com_fdshop&view=product&id=900100&catid=900010');
  const detailAction = page.locator('.fdshop-product [data-fdshop-purchase]');
  const detailPrice = page.locator('.fdshop-product__price');
  const detailPriceSize = await detailPrice.evaluate(element => getComputedStyle(element).fontSize);
  expect(await page.locator('.fdshop-product__purchase-zone').evaluate(element => {
    const price = element.querySelector('.fdshop-product__price').getBoundingClientRect();
    const action = element.querySelector('[data-fdshop-purchase]').getBoundingClientRect();
    return action.left > price.left && action.top >= price.top && action.bottom <= price.bottom;
  })).toBe(true);
  await expect(detailAction.locator('[data-purchase-quantity]')).toHaveCSS('opacity', '0');
  await detailAction.hover();
  await expect(detailAction.locator('[data-purchase-quantity]')).toHaveCSS('opacity', '1');
  await expect(detailAction.locator('[data-purchase-submit]')).toHaveCSS('border-radius', '50%');
  await expect(detailPrice).toHaveCSS('font-size', detailPriceSize);
  await authenticateSiteUser(page);
  await page.goto('/batterien');
  await page.locator('[data-product-id="900100"] [data-purchase-submit]').tap();
  await page.locator('[data-product-id="900100"] [data-purchase-submit]').tap();
  await expect(page.locator('[data-purchase-modal]')).toBeVisible();
  await page.locator('[data-purchase-cart]').click();
  await expect(page.locator('[data-fdshop-cart] [data-cart-item]')).toHaveCount(1);
  diagnostics.expectClean();
  await context.close();
});
