const { test, expect } = require('@playwright/test');
const { authenticateSiteUser, installDiagnostics } = require('../support/browser');

async function openCategory(page, id) {
  await page.goto(`/index.php?option=com_fdshop&view=category&id=${id}`);
  await expect(page.locator('[data-fdshop-compare]').first()).toBeVisible();
}

async function clearComparison(page) {
  const token = await page.locator('[data-fdshop-compare],[data-comparison-page]').first().getAttribute('data-token');
  await page.evaluate(async ({ token }) => {
    const body = new URLSearchParams({ [token]: '1' });
    await fetch('index.php?option=com_fdshop&task=comparison.clear&format=json', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
  }, { token });
}

async function clearSavedComparisons(page) {
  const token = await page.locator('[data-fdshop-compare],[data-comparison-page]').first().getAttribute('data-token');
  await page.evaluate(async ({ token }) => {
    const state = await fetch('/index.php?option=com_fdshop&task=comparison.state&format=json', { headers: { Accept: 'application/json' } }).then(response => response.json());
    for (const list of (state.data || state).saved || []) {
      const body = new URLSearchParams({ list_id: String(list.id), [token]: '1' });
      await fetch('/index.php?option=com_fdshop&task=comparison.delete&format=json', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
    }
  }, { token });
}

test('guest comparison persists, enforces category transition and renders responsive differences matrix', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await openCategory(page, 900012);
  await clearComparison(page);
  await page.reload();
  const buttons = page.locator('[data-fdshop-compare]');
  await expect(buttons).toHaveCount(4);
  await buttons.nth(0).click();
  await expect(page.locator('.fdshop-comparison-bar')).toContainText('1 von 4');
  await buttons.nth(1).click();
  await expect(page.locator('.fdshop-comparison-bar')).toContainText('2 von 4');

  await page.goto('/index.php?option=com_fdshop&view=search&q=E2E');
  await expect(page.locator('.fdshop-comparison-bar')).toContainText('2 von 4');
  await page.locator('.fdshop-comparison-bar').getByRole('link', { name: 'Zum Vergleich' }).click();
  await expect(page.getByRole('heading', { name: 'Produktvergleich' })).toBeVisible();
  await expect(page.locator('.fdshop-comparison-product')).toHaveCount(2);
  await expect(page.locator('.fdshop-comparison-product [data-fdshop-favorite]')).toHaveCount(2);
  await expect(page.getByText('Ausverkauft', { exact: true })).toBeVisible();
  await page.locator('[data-comparison-differences]').check();
  await expect(page.locator('[data-compare-row="Hersteller"]')).toBeHidden();
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true);

  await openCategory(page, 900010);
  const dismissed = page.waitForEvent('dialog').then(dialog => dialog.dismiss());
  await page.locator('[data-product-id="900100"] [data-fdshop-compare]').click();
  await dismissed;
  await expect(page.locator('.fdshop-comparison-bar')).toContainText('2 von 4');
  const accepted = page.waitForEvent('dialog').then(dialog => dialog.accept());
  await page.locator('[data-product-id="900100"] [data-fdshop-compare]').click();
  await accepted;
  await expect(page.locator('.fdshop-comparison-bar')).toContainText('1 von 4');
  await clearComparison(page);
  diagnostics.expectClean();
});

test('registered user can save and reopen an owned comparison while mutations require CSRF', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await authenticateSiteUser(page);
  await openCategory(page, 900010);
  await clearSavedComparisons(page);
  await clearComparison(page);
  await page.reload();
  await page.locator('[data-product-id="900100"] [data-fdshop-compare]').click();
  await page.locator('[data-product-id="900108"] [data-fdshop-compare]').click();
  await expect(page.locator('.fdshop-comparison-bar')).toContainText('2 von 4');
  await page.goto('/index.php?option=com_fdshop&view=comparison');
  await page.locator('[data-comparison-save] input[name="name"]').fill('Mein Testvergleich');
  await page.getByRole('button', { name: 'Aktuellen Vergleich speichern' }).click();
  await expect(page.locator('[data-comparison-list]')).toContainText('Mein Testvergleich');
  const invalid = await page.request.post('/index.php?option=com_fdshop&task=comparison.delete&format=json', { form: { list_id: 999999 } });
  expect((await invalid.json()).success).toBe(false);
  await page.getByRole('button', { name: 'Vergleich leeren' }).click();
  await expect(page.locator('.fdshop-comparison-empty')).toBeVisible();
  await page.locator('[data-comparison-list]').getByRole('button', { name: 'Öffnen' }).click();
  await expect(page.locator('.fdshop-comparison-product')).toHaveCount(2);
  page.once('dialog', dialog => dialog.accept());
  await page.locator('[data-comparison-list]').getByRole('button', { name: 'Löschen' }).click();
  await expect(page.locator('[data-comparison-list]')).toHaveCount(0);
  await clearComparison(page);
  diagnostics.expectClean();
});

test('server publishes the configured limit and enforces same-category membership', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await openCategory(page, 900012);
  await clearComparison(page);
  await page.reload();
  const buttons = page.locator('[data-fdshop-compare]');
  for (let index = 0; index < 2; index += 1) {
    await buttons.nth(index).click();
    await expect(page.locator('.fdshop-comparison-bar')).toContainText(`${index + 1} von 4`);
  }
  const token = await buttons.first().getAttribute('data-token');
  const state = await page.request.get('/index.php?option=com_fdshop&task=comparison.state&format=json');
  expect((await state.json()).data.max).toBe(4);
  const wrongCategory = await page.request.post('/index.php?option=com_fdshop&task=comparison.add&format=json', { form: { product_id: 900100, category_id: 900012, replace: 1, [token]: '1' } });
  expect(await wrongCategory.json()).toMatchObject({ success: false, message: /nicht vergleichbar/ });
  diagnostics.expectClean();
});

test('comparison action survives the shared dynamic product-card lifecycle', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await openCategory(page, 900012);
  await clearComparison(page);
  const card = page.locator('.fdshop-card').first();
  await card.locator('[data-fdshop-compare]').evaluate(element => element.remove());
  await page.evaluate(() => document.dispatchEvent(new CustomEvent('fdshop:product-cards-updated')));
  const action = card.locator('[data-fdshop-compare]');
  await expect(action).toBeEnabled();
  await action.click();
  await expect(page.locator('.fdshop-comparison-bar')).toContainText('1 von 4');
  diagnostics.expectClean();
});

test('feedback bar clears without reload and comparison video uses the shared dialog', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.route('https://www.youtube-nocookie.com/**', route => route.fulfill({ status: 200, contentType: 'text/html', body: '<!doctype html><title>Video fixture</title>' }));
  await openCategory(page, 900010);
  await clearComparison(page);
  await page.reload();
  await page.locator('[data-product-id="900100"] [data-fdshop-compare]').click();
  const bar = page.locator('.fdshop-comparison-bar');
  await expect(bar).toBeVisible();
  await bar.getByRole('button', { name: 'Leeren' }).click();
  await expect(bar).toBeHidden();
  await expect(page.locator('[data-product-id="900100"] [data-fdshop-compare]')).toHaveAttribute('aria-pressed', 'false');

  await page.locator('[data-product-id="900100"] [data-fdshop-compare]').click();
  await bar.getByRole('link', { name: 'Zum Vergleich' }).click();
  const video = page.locator('.fdshop-comparison-product [data-fdshop-video]');
  await expect(video).toHaveCount(1);
  await video.click();
  const dialog = page.locator('[data-fdshop-video-dialog]');
  await expect(dialog).toBeVisible();
  await expect(dialog.locator('iframe')).toHaveCount(1);
  await dialog.getByRole('button', { name: 'Video schließen' }).click();
  await expect(dialog).toBeHidden();
  diagnostics.expectClean();
});
