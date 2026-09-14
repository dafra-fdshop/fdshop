const { test, expect } = require('@playwright/test');
const { installDiagnostics } = require('../support/browser');

test('product detail switches server-prepared package data and keeps piece/package cart identities separate', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.goto('/index.php?option=com_fdshop&view=product&id=900103&catid=900011');
  const product = page.locator('[data-fdshop-package-root]');
  const select = product.locator('[data-fdshop-package-select]');
  await expect(select).toHaveValue('piece');
  await select.selectOption('package');
  await expect(product.locator('[data-fdshop-package-name]')).toHaveText('E2E Produkt Bild Display');
  await expect(product.locator('[data-fdshop-package-price]')).toHaveText('138,00 EUR');
  await expect(product.locator('[data-fdshop-package-fact="nem"]')).toHaveText('24 g');
  await expect(product.locator('[data-fdshop-package-fact="shots"]')).toHaveText('36');
  const packageResponse = page.waitForResponse(response => response.url().includes('task=cart.add'));
  await product.locator('[data-purchase-submit]').click();
  expect((await (await packageResponse).json()).data.purchase).toMatchObject({ unitVariant: 'package', unitType: 'Display', unitPrice: '138,00 €' });
  await page.getByRole('button', { name: 'Weiter einkaufen' }).click();
  await select.selectOption('piece');
  const pieceResponse = page.waitForResponse(response => response.url().includes('task=cart.add'));
  await product.locator('[data-purchase-submit]').click();
  expect((await (await pieceResponse).json()).data.purchase).toMatchObject({ unitVariant: 'piece', unitType: 'Stück', unitPrice: '23,00 €' });
  await page.getByRole('button', { name: 'Weiter einkaufen' }).click();
  await select.selectOption('package');
  await product.locator('[data-purchase-quantity]').fill('6');
  const limitedResponse = page.waitForResponse(response => response.url().includes('task=cart.add'));
  await product.locator('[data-purchase-submit]').click();
  expect((await (await limitedResponse).json()).data.purchase).toMatchObject({ unitVariant: 'package', effectiveQuantity: 5, resultingCartQuantity: 6, adjusted: true });
  await page.locator('[data-purchase-cart]').click();
  await expect(page.locator('[data-cart-item]')).toHaveCount(2);
  await expect(page.locator('.fdshop-cart__product')).toContainText(['E2E Produkt Bild Display', 'E2E Produkt Bild']);
  await expect(page.locator('.fdshop-cart__sku')).toContainText(['E2E-PROD-IMAGE-DISPLAY', 'E2E-PROD-IMAGE']);
  diagnostics.expectClean();
});

test('package pricing supports percent and fixed gross amount while category sends piece explicitly', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.goto('/index.php?option=com_fdshop&view=product&id=900104&catid=900011');
  await page.locator('[data-fdshop-package-select]').selectOption('package');
  await expect(page.locator('[data-fdshop-package-price]')).toHaveText('136,80 EUR');
  await page.goto('/index.php?option=com_fdshop&view=product&id=900105&catid=900012');
  await page.locator('[data-fdshop-package-select]').selectOption('package');
  await expect(page.locator('[data-fdshop-package-price]')).toHaveText('199,95 EUR');
  await page.goto('/batterien?limit=48');
  await expect(page.locator('[data-product-id="900108"] .fdshop-ribbon--package')).toHaveText('SCHINKEN');
  await expect(page.locator('[data-product-id="900108"] [data-fdshop-purchase]')).toHaveAttribute('data-unit-variant', 'piece');
  diagnostics.expectClean();
});
