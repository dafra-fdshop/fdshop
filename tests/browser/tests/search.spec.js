const { test, expect } = require('@playwright/test');
const { installDiagnostics } = require('../support/browser');

async function openCategory(page) {
  const response = await page.goto('/index.php?option=com_fdshop&view=category&id=900010');
  expect(response?.status()).toBe(200);
  await expect(page.locator('[data-fdshop-search]')).toBeVisible();
}

test('search suggestions are accessible, current and lead to products and all results', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await openCategory(page);
  const input = page.locator('[data-fdshop-search-input]');
  await expect(input).toHaveAttribute('role', 'combobox');
  await input.fill('E2');
  await page.waitForTimeout(350);
  await expect(page.locator('[data-fdshop-search-suggestions]')).toBeHidden();

  await input.fill('E2E');
  await expect(page.locator('[role="option"]').first()).toBeVisible();
  await expect(page.locator('[role="option"]')).toHaveCount(8);
  await expect(page.locator('[role="option"]').first()).toContainText('E2E Produkt');
  await expect(page.locator('[role="option"]').first()).toContainText('E2E Hersteller Aktiv');
  await expect(page.locator('[role="option"]').first()).toContainText('EUR');

  await input.press('ArrowDown');
  await expect(input).toHaveAttribute('aria-activedescendant', /option-0$/);
  const target = await page.locator('[role="option"]').first().locator('a').getAttribute('href');
  await input.press('Enter');
  await expect(page).toHaveURL(target);

  await page.goBack();
  await input.fill('E2E');
  const all = page.getByRole('link', { name: 'Alle Ergebnisse für „E2E“' });
  await expect(all).toBeVisible();
  await all.click();
  await expect(page).toHaveURL(/view=search|\/search/);
  await expect(page.locator('.fdshop-search-page__summary')).toHaveText('8 Treffer für „E2E“');
  diagnostics.expectClean();
});

test('search covers name sku manufacturer visibility sold-out and empty state', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  for (const [query, product] of [
    ['Produkt Aktiv', 'E2E Produkt Aktiv'],
    ['  e2e-prod-active  ', 'E2E Produkt Aktiv'],
    ['E2E Hersteller Aktiv', 'E2E Produkt Aktiv'],
  ]) {
    await page.goto(`/index.php?option=com_fdshop&view=search&q=${encodeURIComponent(query)}`);
    await expect(page.locator('.fdshop-card', { hasText: product }).first()).toBeVisible();
  }
  await page.goto('/index.php?option=com_fdshop&view=search&q=E2E');
  await expect(page.locator('[data-product-id="900106"]')).toContainText('Ausverkauft');
  await expect(page.locator('[data-product-id="900101"]')).toHaveCount(0);
  await expect(page.locator('[data-product-id="900102"]')).toHaveCount(0);
  await expect(page.locator('.fdshop-card')).toHaveCount(8);
  await expect(page.locator('[data-product-id="900100"] [data-fdshop-purchase]')).toBeVisible();

  await page.goto('/index.php?option=com_fdshop&view=search&q=garantiert-kein-produkt');
  await expect(page.locator('.fdshop-search-page__empty')).toContainText('Keine Produkte gefunden');
  diagnostics.expectClean();
});

test('rapid typing ignores stale suggestions and search works on mobile', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.setViewportSize({ width: 390, height: 844 });
  await openCategory(page);
  await page.route('**task=search.suggest**', async route => {
    if (route.request().url().includes('q=E2E')) await new Promise(resolve => setTimeout(resolve, 500));
    await route.continue();
  });
  const input = page.locator('[data-fdshop-search-input]');
  await input.fill('E2E');
  await page.waitForTimeout(300);
  await input.fill('E2E-PROD-ACTIVE');
  await expect(page.locator('[role="option"]')).toHaveCount(1);
  await expect(page.locator('[role="option"]')).toContainText('E2E Produkt Aktiv');
  expect(await page.locator('[data-fdshop-search]').evaluate(element => element.scrollWidth <= element.clientWidth)).toBe(true);
  await input.press('Escape');
  await expect(page.locator('[data-fdshop-search-suggestions]')).toBeHidden();
  diagnostics.expectClean();
});
