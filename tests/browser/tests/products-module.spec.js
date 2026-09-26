const { test, expect } = require('@playwright/test');

const categoryId = process.env.FDSHOP_PRODUCTS_MODULE_CATEGORY_ID;
const url = `/index.php?option=com_fdshop&view=category&id=${categoryId}`;
const standaloneUrl = '/index.php';

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
  await expect(modules.nth(1).getByRole('link', { name: 'Alle Artikel' })).toHaveAttribute('href', /view=category|\/category/);
  expect(await modules.first().getAttribute('data-query-count')).toBe('3');
  expect(await page.locator('[data-fdshop-products-module][data-request-cache-hit="1"]').count()).toBe(7);

  const quantityIds = await modules.locator('[data-purchase-quantity]').evaluateAll(nodes => nodes.map(node => node.id));
  expect(new Set(quantityIds).size).toBe(quantityIds.length);
});

test('standalone page registers central assets and supports all category link modes', async ({ page }) => {
  const pageErrors = [];
  page.on('pageerror', error => pageErrors.push(error.message));
  const response = await page.goto(standaloneUrl);
  expect(response.status()).toBe(200);
  const modules = page.locator('[data-fdshop-products-module]');
  await expect(modules).toHaveCount(8);

  const styles = await page.locator('link[rel="stylesheet"]').evaluateAll(nodes => nodes.map(node => node.href).filter(href => href.includes('/media/com_fdshop/css/site.css')));
  const purchaseScripts = await page.locator('script[src]').evaluateAll(nodes => nodes.map(node => node.src).filter(src => src.includes('/media/com_fdshop/js/purchase.js')));
  const moduleScripts = await page.locator('script[src]').evaluateAll(nodes => nodes.map(node => node.src).filter(src => src.includes('/media/com_fdshop/js/products-module.js')));
  expect(styles).toHaveLength(1);
  expect(purchaseScripts).toHaveLength(1);
  expect(moduleScripts).toHaveLength(1);

  const none = page.locator('[data-link-category="none"]').first();
  await expect(none.locator('.fdshop-products-module__heading h2 a')).toHaveCount(0);
  await expect(none.locator('[data-category-link]')).toHaveCount(0);

  const titleButton = page.locator('[data-link-category="title_button"]').first();
  const titleHref = await titleButton.locator('.fdshop-products-module__heading h2 a').getAttribute('href');
  await expect(titleButton.getByRole('link', { name: 'Alle Artikel' })).toHaveAttribute('href', titleHref);

  const titleOnly = page.locator('[data-link-category="title_only"]').first();
  await expect(titleOnly.locator('.fdshop-products-module__heading h2 a')).toHaveCount(1);
  await expect(titleOnly.locator('[data-category-link]')).toHaveCount(0);

  await expect(page.getByText('FDShop Produkte 6', { exact: true })).toHaveCount(1);
  const legacyHidden = page.getByRole('heading', { name: 'FDShop Produkte 4' }).locator('xpath=ancestor::section[@data-fdshop-products-module]');
  const legacyVisible = page.getByRole('heading', { name: 'FDShop Produkte 5' }).locator('xpath=ancestor::section[@data-fdshop-products-module]');
  await expect(legacyHidden).toHaveAttribute('data-link-category', 'none');
  await expect(legacyHidden.locator('[data-category-link]')).toHaveCount(0);
  await expect(legacyVisible).toHaveAttribute('data-link-category', 'title_button');
  await expect(legacyVisible.locator('[data-category-link]')).toHaveCount(1);
  expect(pageErrors).toEqual([]);
});

test('standalone header aligns controls and purchase action remains functional', async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto(standaloneUrl);
  const module = page.locator('[data-link-category="title_button"]').first();
  const header = module.locator('.fdshop-products-module__header');
  const heading = module.locator('.fdshop-products-module__heading');
  const navigation = module.locator('.fdshop-products-module__navigation');
  const viewport = module.locator('[data-products-viewport]');
  const boxes = await Promise.all([heading, navigation, viewport].map(locator => locator.boundingBox()));
  expect(Math.abs(boxes[0].y - boxes[1].y)).toBeLessThan(12);
  expect(boxes[1].x).toBeGreaterThan(boxes[0].x + boxes[0].width);
  expect(boxes[2].y).toBeGreaterThan(boxes[0].y + boxes[0].height - 2);
  const colours = await navigation.locator('button').first().evaluate(node => {
    const style = getComputedStyle(node);
    return { background: style.backgroundColor, color: style.color };
  });
  expect(colours.background).toBe('rgb(0, 0, 0)');
  expect(colours.color).toBe('rgb(255, 255, 255)');

  await module.locator('[data-purchase-submit]').first().click();
  await expect(page.locator('[data-purchase-modal]')).toHaveCount(1);
  await expect(page.locator('[data-purchase-modal]')).toBeVisible();
  await expect(page.locator('[data-purchase-message]')).not.toBeEmpty();

  await page.setViewportSize({ width: 390, height: 844 });
  await expect(header).toBeVisible();
  const headerBox = await header.boundingBox();
  expect(headerBox.width).toBeLessThanOrEqual(390);
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
