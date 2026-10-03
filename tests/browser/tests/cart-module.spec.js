const { test, expect } = require('@playwright/test');
const { authenticateSiteUser, installDiagnostics } = require('../support/browser');

const modules = page => page.locator('[data-fdshop-cart-module]');

test('cart module renders twice without duplicate assets and behaves as an accessible responsive offcanvas', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  let summaryRequests = 0;
  page.on('request', request => { if (request.url().includes('task=cart.summary')) summaryRequests += 1; });
  await page.goto('/warenkorb');
  await expect(modules(page)).toHaveCount(2);
  await expect(page.locator('link[href*="cart-module.css"]')).toHaveCount(1);
  await expect(page.locator('script[src*="cart-module.js"]')).toHaveCount(1);
  await expect(modules(page).first().locator('[data-cart-module-count]')).toHaveText('0');
  await expect(modules(page).first().locator('[data-cart-module-empty]')).toHaveText('Dein Warenkorb ist noch leer.');
  expect(summaryRequests, 'initial state is rendered server-side').toBe(0);

  for (const width of [320, 375, 430, 1280]) {
    await page.setViewportSize({ width, height: 760 });
    const trigger = modules(page).first().locator('[data-cart-module-open]');
    await trigger.click();
    const panel = modules(page).first().locator('[data-cart-module-panel]');
    await expect(panel).toBeVisible();
    await expect(panel).toHaveClass(/is-open/);
    await expect.poll(async () => {
      const box = await panel.boundingBox();
      return Math.ceil(box.x + box.width);
    }).toBeLessThanOrEqual(width + 1);
    await expect(page.locator('html')).toHaveClass(/fdshop-cart-offcanvas-open/);
    await page.keyboard.press('Escape');
    await expect(panel).toBeHidden();
    await expect(trigger).toBeFocused();
  }

  const trigger = modules(page).first().locator('[data-cart-module-open]');
  await trigger.click();
  await modules(page).first().locator('[data-cart-module-backdrop]').click({ position: { x: 2, y: 2 } });
  await expect(modules(page).first().locator('[data-cart-module-panel]')).toBeHidden();

  // A rapid close/reopen must not let a stale close timer hide the reopened panel.
  await trigger.click();
  await page.keyboard.press('Escape');
  await trigger.evaluate(button => button.click());
  await page.waitForTimeout(260);
  await expect(modules(page).first().locator('[data-cart-module-panel]')).toBeVisible();
  await page.keyboard.press('Escape');
  await expect(modules(page).first().locator('[data-cart-module-panel]')).toBeHidden();

  await page.emulateMedia({ reducedMotion: 'reduce' });
  await trigger.click();
  await expect(modules(page).first().locator('[data-cart-module-panel]')).toHaveCSS('transition-duration', '0s');
  await modules(page).first().locator('[data-cart-module-close]').click();
  await expect(modules(page).first().locator('[data-cart-module-panel]')).toBeHidden();
  diagnostics.expectClean();
});

test('all module instances live-update once after quantity changes and preserve decimal counts', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await authenticateSiteUser(page);
  await page.goto('/warenkorb');
  await expect(modules(page)).toHaveCount(2);
  await expect(modules(page).first().locator('[data-cart-module-count]')).toHaveText('4');

  let summaryRequests = 0;
  page.on('request', request => { if (request.url().includes('task=cart.summary')) summaryRequests += 1; });
  const item = page.locator('[data-cart-item="910000"]');
  await item.getByRole('button', { name: 'Menge erhöhen' }).click();
  await item.getByRole('button', { name: 'Menge aktualisieren' }).click();
  await expect(modules(page).first().locator('[data-cart-module-count]')).toHaveText('5');
  await expect(modules(page).nth(1).locator('[data-cart-module-count]')).toHaveText('5');
  expect(summaryRequests, 'one central refresh serves every module instance').toBe(1);

  // Restore the shared fixture row before subsequent cart regressions run.
  await item.getByRole('button', { name: 'Menge reduzieren' }).click();
  await item.getByRole('button', { name: 'Menge aktualisieren' }).click();
  await expect(modules(page).first().locator('[data-cart-module-count]')).toHaveText('4');

  await page.route(/task=cart\.summary/, route => route.fulfill({
    status: 200,
    contentType: 'application/json',
    body: JSON.stringify({ success: true, message: null, messages: null, data: {
      count: 1.5, countFormatted: '1,5', subtotal: '29,99 €', empty: false,
      positions: [{ type: 'product', name: 'Dezimalprodukt', quantity: '1,5', total: '29,99 €', image: '/media/com_fdshop/images/product-placeholder.svg', url: '' }],
    } }),
  }));
  await page.evaluate(() => document.dispatchEvent(new CustomEvent('fdshop:cart-updated')));
  await expect(modules(page).first().locator('[data-cart-module-count]')).toHaveText('1,5');
  await expect(modules(page).first().locator('[data-cart-module-items]')).toContainText('1,5 × Dezimalprodukt');
  diagnostics.expectClean();
});

test('a cart bundle is represented as one live-updated position without child rows', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.route('https://i.ytimg.com/**', route => route.fulfill({ status: 200, contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg"/>' }));
  await page.goto('/index.php?option=com_fdshop&view=product&id=900100');
  await page.getByRole('button', { name: 'Bundle erstellen' }).click();
  const dialog = page.locator('[data-fdshop-bundle-dialog]');
  await expect(dialog.getByRole('heading', { name: 'E2E Bundle Aktiv' })).toBeVisible();
  await dialog.locator('[data-bundle-pool-product="900100"] [data-bundle-add]').click();
  await dialog.locator('[data-bundle-pool-product="900105"] [data-bundle-add]').click();
  await dialog.getByRole('button', { name: 'In den Warenkorb' }).click();
  await expect(page).toHaveURL(/view=cart|warenkorb/);

  const module = modules(page).first();
  await expect(module.locator('[data-cart-module-count]')).toHaveText('1');
  await module.locator('[data-cart-module-open]').click();
  await expect(module.locator('[data-cart-module-position="bundle"]')).toHaveCount(1);
  await expect(module.locator('[data-cart-module-position="bundle"]')).toContainText('1 × E2E Bundle Aktiv');
  await expect(module.locator('[data-cart-module-items] > li')).toHaveCount(1);
  diagnostics.expectClean();
});
