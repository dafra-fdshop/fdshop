const { test, expect } = require('@playwright/test');
const { authenticateSiteUser, installDiagnostics } = require('../support/browser');

const modules = page => page.locator('[data-fdshop-favorites-module]');

async function cleanFavorites(page) {
  await page.goto('/index.php?option=com_fdshop&view=category&id=900010');
  await expect(page.locator('[data-product-id="900100"] [data-fdshop-favorite]')).toBeVisible();
  await page.evaluate(async () => {
    const button = document.querySelector('[data-fdshop-favorite]');
    const read = await fetch(`${button.dataset.listsUrl}&product_id=${button.dataset.favoriteProductId}`).then(response => response.json());
    const lists = (read.data || read).lists || [];
    for (const productId of [900100, 900105]) {
      const body = new URLSearchParams({ product_id: productId, [button.dataset.token]: '1' });
      await fetch(button.dataset.saveUrl, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
    }
    for (const list of lists.filter(item => !Number(item.is_default))) {
      const body = new URLSearchParams({ list_id: list.id, [button.dataset.token]: '1' });
      await fetch('index.php?option=com_fdshop&task=favorite.deleteList&format=json', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
    }
  });
  await page.reload();
}

test('guest module is compact, accessible, multi-instance safe and follows the established login route', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.goto('/index.php?option=com_fdshop&view=category&id=900010');
  await expect(modules(page)).toHaveCount(2);
  await expect(page.locator('link[href*="favorites-module.css"]')).toHaveCount(1);
  await expect(page.locator('script[src*="favorites-module.js"]')).toHaveCount(1);
  await expect(modules(page).first().locator('[data-favorites-module-count]')).toHaveText('0');
  const link = modules(page).first().getByRole('link');
  await expect(link).toHaveAttribute('aria-label', /Anmeldung erforderlich/);
  await expect(link).toHaveAttribute('href', /(?:view=favorites|favorites|favoriten)/);

  for (const width of [320, 375, 430, 1280]) {
    await page.setViewportSize({ width, height: 720 });
    await expect(link).toBeVisible();
    const box = await link.boundingBox();
    expect(box.x).toBeGreaterThanOrEqual(0);
    expect(box.x + box.width).toBeLessThanOrEqual(width + 1);
  }
  await link.focus();
  await expect(link).toBeFocused();
  await page.keyboard.press('Enter');
  await expect(page).toHaveURL(/(?:option=com_users.*view=login|component\/users\/login)/);
  diagnostics.expectClean();
});

test('counter tracks only the protected default list and updates every module without reload', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await authenticateSiteUser(page);
  await cleanFavorites(page);
  await expect(modules(page)).toHaveCount(2);
  await expect(modules(page).first().locator('[data-favorites-module-count]')).toHaveText('0');

  let countRequests = 0;
  page.on('request', request => { if (request.url().includes('task=favorite.counter')) countRequests += 1; });
  await page.evaluate(() => document.dispatchEvent(new CustomEvent('fdshop:favorites-updated')));
  await expect.poll(() => countRequests).toBe(1);

  const first = page.locator('[data-product-id="900100"] [data-fdshop-favorite]');
  await first.click();
  await expect(modules(page).first().locator('[data-favorites-module-count]')).toHaveText('1');
  await expect(modules(page).nth(1).locator('[data-favorites-module-count]')).toHaveText('1');

  await page.evaluate(async () => {
    const button = document.querySelector('[data-fdshop-favorite]');
    const body = new URLSearchParams({ name: 'E2E Zusatzliste', [button.dataset.token]: '1' });
    const response = await fetch('index.php?option=com_fdshop&task=favorite.createList&format=json', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
    const payload = await response.json();
    if (payload.success === false) throw new Error(payload.message);
  });

  const second = page.locator('[data-product-id="900105"] [data-fdshop-favorite]');
  await second.click();
  let chooser = page.locator('.fdshop-favorite-dialog');
  await chooser.getByLabel(/Standardliste/).check();
  await chooser.getByLabel(/E2E Zusatzliste/).check();
  await chooser.getByRole('button', { name: 'Auswahl speichern' }).click();
  await expect(modules(page).first().locator('[data-favorites-module-count]')).toHaveText('2');

  await second.click();
  chooser = page.locator('.fdshop-favorite-dialog');
  await chooser.getByLabel(/Standardliste/).uncheck();
  const finalSave = page.waitForResponse(response => response.url().includes('task=favorite.saveMemberships'));
  await chooser.getByRole('button', { name: 'Auswahl speichern' }).click();
  const finalPayload = await (await finalSave).json();
  expect((finalPayload.data || finalPayload).default_count).toBe(1);
  await expect(modules(page).first().locator('[data-favorites-module-count]')).toHaveText('1');
  await expect(modules(page).nth(1).locator('[data-favorites-module-count]')).toHaveText('1');
  expect(countRequests, 'successful mutations carry the authoritative count in their event').toBe(1);

  await cleanFavorites(page);
  diagnostics.expectClean();
});
