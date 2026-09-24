const { test, expect } = require('@playwright/test');
const { authenticateAdministrator, installDiagnostics } = require('../support/browser');

const productId = process.env.FDSHOP_MEDIA_TEST_PRODUCT_ID;
test.skip(!productId, 'FDSHOP_MEDIA_TEST_PRODUCT_ID is required');

test('migrated media is normal admin media and responsive frontend media', async ({ page, context, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await authenticateAdministrator(page, context, test.info());
  await page.goto(`/administrator/index.php?option=com_fdshop&view=product&layout=edit&id=${productId}`);
  await page.getByRole('tab', { name: 'Medien' }).click();
  await expect(page.locator('[data-fdshop-media-item]')).toHaveCount(1);
  await expect(page.locator('[data-fdshop-media-item]').getByText('Hauptbild', { exact: true })).toBeVisible();
  await expect(page.locator('[data-fdshop-media-item] img')).toHaveAttribute('src', /\/images\/FDShop\/products\/small\/product-.*\.webp$/);

  await page.setViewportSize({ width: 768, height: 900 });
  await page.goto('/batterien?limit=48');
  const card = page.locator(`[data-product-id="${productId}"]`);
  await expect(card).toBeVisible();
  const image = card.locator('.fdshop-card__product-image');
  await image.scrollIntoViewIfNeeded();
  await expect(image).toBeVisible();
  await expect.poll(() => image.evaluate(element => element.currentSrc)).toContain('/small/');
  const detailHref = await card.getByRole('link', { name: 'Details', exact: true }).getAttribute('href');

  await page.setViewportSize({ width: 390, height: 900 });
  await page.goto('/batterien?limit=48');
  const mobileImage = page.locator(`[data-product-id="${productId}"] .fdshop-card__product-image`);
  await mobileImage.scrollIntoViewIfNeeded();
  await expect(mobileImage).toBeVisible();
  await expect.poll(() => mobileImage.evaluate(element => element.currentSrc)).toContain('/mobile/');

  await page.goto(detailHref);
  const detailImage = page.locator('.fdshop-product__main-image img');
  await expect(detailImage).toBeVisible();
  await expect(detailImage).toHaveAttribute('src', /\/standard\/product-.*\.webp$/);

  await page.goto('/batterien?limit=48');
  await expect(page.locator('[data-product-id="900104"] .fdshop-card__placeholder')).toBeVisible();
  diagnostics.expectClean();
});
