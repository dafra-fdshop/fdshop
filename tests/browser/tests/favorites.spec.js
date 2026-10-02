const { test, expect } = require('@playwright/test');
const { authenticateSiteUser, installDiagnostics } = require('../support/browser');

async function openProductCards(page) {
  await page.goto('/index.php?option=com_fdshop&view=category&id=900010');
  await expect(page.locator('[data-product-id="900100"] [data-fdshop-favorite]')).toBeVisible();
}

async function cleanUserFavorites(page) {
  await openProductCards(page);
  await page.evaluate(async () => {
    const button = document.querySelector('[data-fdshop-favorite]');
    const read = await fetch(`${button.dataset.listsUrl}&product_id=${button.dataset.favoriteProductId}`).then(response => response.json());
    const lists = (read.data || read).lists || [];
    for (const list of lists.filter(item => !Number(item.is_default))) {
      const body = new URLSearchParams({ list_id: list.id, [button.dataset.token]: '1' });
      await fetch('index.php?option=com_fdshop&task=favorite.deleteList&format=json', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
    }
    const body = new URLSearchParams({ product_id: button.dataset.favoriteProductId, [button.dataset.token]: '1' });
    await fetch(button.dataset.saveUrl, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
  });
}

test('guest receives login and registration invitation without local persistence', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await openProductCards(page);
  const heart = page.locator('[data-product-id="900100"] [data-fdshop-favorite]');
  await expect(heart).toHaveAttribute('aria-pressed', 'false');
  await heart.click();
  const dialog = page.locator('.fdshop-favorite-dialog');
  await expect(dialog).toBeVisible();
  await expect(dialog).toContainText('Favoriten sind für registrierte Kunden verfügbar');
  await expect(dialog.getByRole('link', { name: 'Anmelden' })).toBeVisible();
  await expect(dialog.getByRole('link', { name: 'Registrieren' })).toBeVisible();
  expect(await page.evaluate(() => Object.keys(localStorage).filter(key => /favor/i.test(key)))).toEqual([]);
  diagnostics.expectClean();
});

test('authenticated favorites support default toggle, list CRUD, multi-list selection and ownership protections', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await authenticateSiteUser(page);
  await cleanUserFavorites(page);
  await openProductCards(page);
  const heart = page.locator('[data-product-id="900100"] [data-fdshop-favorite]');
  if (await heart.getAttribute('aria-pressed') === 'true') await heart.click();
  const listsResponse = page.waitForResponse(response => response.url().includes('task=favorite.lists'));
  const toggleResponse = page.waitForResponse(response => response.url().includes('task=favorite.toggle'));
  await heart.click();
  const listsBody = await (await listsResponse).json();
  expect(listsBody.success, JSON.stringify(listsBody)).toBe(true);
  expect(await (await toggleResponse).json()).toMatchObject({ success: true });
  await expect(heart).toHaveAttribute('aria-pressed', 'true');

  await page.goto('/index.php?option=com_fdshop&view=favorites');
  await expect(page.getByRole('heading', { name: 'Meine Favoriten' })).toBeVisible();
  await expect(page.getByRole('link', { name: /Standardliste/ })).toBeVisible();
  await expect(page.locator('[data-product-id="900100"]')).toBeVisible();
  await expect(page.locator('[data-favorite-rename]')).toHaveCount(0);
  await expect(page.locator('[data-favorite-delete]')).toHaveCount(0);

  for (const name of ['Silvester', 'Familie', 'Merkliste']) {
    await page.getByLabel('Neue Liste').fill(name);
    await page.getByRole('button', { name: 'Anlegen' }).click();
    await expect(page.getByRole('link', { name: new RegExp(name) })).toBeVisible();
  }
  page.once('dialog', dialog => { expect(dialog.message()).toContain('maximale Anzahl'); dialog.dismiss(); });
  await page.getByLabel('Neue Liste').fill('Zu viel');
  await page.getByRole('button', { name: 'Anlegen' }).click();

  await openProductCards(page);
  await heart.click();
  const chooser = page.locator('.fdshop-favorite-dialog');
  await expect(chooser.getByRole('heading', { name: 'Favoritenlisten' })).toBeVisible();
  await chooser.getByLabel(/Silvester/).check();
  await chooser.getByRole('button', { name: 'Auswahl speichern' }).click();
  await expect(heart).toHaveAttribute('aria-pressed', 'true');

  const invalid = await page.request.post('/index.php?option=com_fdshop&task=favorite.deleteList&format=json', { form: { list_id: 999999 } });
  expect((await invalid.json()).success).toBe(false);

  await page.goto('/index.php?option=com_fdshop&view=favorites');
  await page.getByRole('link', { name: /Silvester/ }).click();
  await expect(page.locator('[data-favorite-rename]')).toBeVisible();
  page.once('dialog', async dialog => dialog.accept('Feuerwerk')); 
  await page.locator('[data-favorite-rename]').click();
  await expect(page.getByRole('link', { name: /Feuerwerk/ })).toBeVisible();
  await cleanUserFavorites(page);
  diagnostics.expectClean();
});
