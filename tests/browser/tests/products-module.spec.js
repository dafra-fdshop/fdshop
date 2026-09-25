const { test, expect } = require('@playwright/test');

const categoryId = process.env.FDSHOP_PRODUCTS_MODULE_CATEGORY_ID;
const url = `/index.php?option=com_fdshop&view=category&id=${categoryId}`;

test('eight module instances remain isolated and reuse request data', async ({ page }) => {
  await page.goto(url);
  const modules = page.locator('[data-fdshop-products-module]');
  await expect(modules).toHaveCount(8);
  const ids = await modules.evaluateAll(nodes => nodes.map(node => node.id));
  expect(new Set(ids).size).toBe(8);
  await expect(page.locator('[data-fdshop-products-module][data-cache-mode="0"]')).toHaveCount(4);
  await expect(page.locator('[data-fdshop-products-module][data-cache-mode="1"]')).toHaveCount(4);
  await expect(modules.first().locator('.fdshop-card')).toHaveCount(8);
  await expect(modules.first().locator('[data-products-next]')).toBeVisible();
  await expect(modules.first().locator('img[loading="lazy"]').first()).toBeVisible();
  await expect(modules.first().getByRole('link', { name: 'Zur Kategorie' })).toHaveAttribute('href', /view=category|\/category/);
  expect(await modules.first().getAttribute('data-query-count')).toBe('3');
  expect(await page.locator('[data-fdshop-products-module][data-request-cache-hit="1"]').count()).toBe(7);

  const quantityIds = await modules.locator('[data-purchase-quantity]').evaluateAll(nodes => nodes.map(node => node.id));
  expect(new Set(quantityIds).size).toBe(quantityIds.length);
});

test('carousel navigates only its own instance', async ({ page }) => {
  await page.goto(url);
  const modules = page.locator('[data-fdshop-products-module]');
  const firstViewport = modules.nth(0).locator('[data-products-viewport]');
  const secondViewport = modules.nth(1).locator('[data-products-viewport]');
  const beforeSecond = await secondViewport.evaluate(node => node.scrollLeft);
  await modules.nth(0).locator('[data-products-next]').click();
  await expect.poll(() => firstViewport.evaluate(node => node.scrollLeft)).toBeGreaterThan(0);
  expect(await secondViewport.evaluate(node => node.scrollLeft)).toBe(beforeSecond);
});

test('responsive card counts and grid mode are usable', async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto(url);
  const first = page.locator('[data-fdshop-products-module]').first();
  const desktopWidth = await first.locator('.fdshop-products-module__card').first().evaluate(node => node.getBoundingClientRect().width);
  const viewportWidth = await first.locator('[data-products-viewport]').evaluate(node => node.getBoundingClientRect().width);
  expect(desktopWidth).toBeLessThan(viewportWidth / 4);

  await page.setViewportSize({ width: 390, height: 844 });
  const mobileWidth = await first.locator('.fdshop-products-module__card').first().evaluate(node => node.getBoundingClientRect().width);
  expect(mobileWidth).toBeGreaterThan(250);
  expect(mobileWidth).toBeLessThan(390);
  const grid = page.locator('[data-fdshop-products-module].is-grid');
  await expect(grid).toHaveCount(1);
  await expect(grid.locator('[data-products-next]')).toHaveCount(0);
  await page.screenshot({ path: 'products-module-mobile.png', fullPage: true });
});
