const { test, expect } = require('@playwright/test');
const { authenticateAdministrator, authenticateSiteUser, installDiagnostics, openView } = require('../support/browser');

test('search module reuses component suggestions while fixed category search defaults off', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.goto('/index.php?option=com_fdshop&view=category&id=900010');
  const module = page.locator('[data-fdshop-search-module]');
  await expect(module).toHaveCount(1);
  await expect(page.locator('.fdshop-category > .fdshop-search')).toHaveCount(0);
  const input = module.locator('[data-fdshop-search-input]');
  await expect(input).toHaveAttribute('role', 'combobox');
  await input.fill('E2E');
  await expect(module.locator('[role="option"]')).toHaveCount(8);
  await input.press('ArrowDown');
  await expect(input).toHaveAttribute('aria-activedescendant', /option-0$/);
  await input.press('Enter');
  await expect(page).toHaveURL(/view=product|\/product/);
  diagnostics.expectClean();
});

test('frontend feature configuration is visible and category-search switch persists', async ({ page, context, baseURL }, testInfo) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await authenticateAdministrator(page, context, testInfo);
  await openView(page, 'configuration');
  await page.getByRole('tab', { name: 'Frontend-Funktionen', exact: true }).click();
  for (const id of ['search_active', 'search_category_active', 'search_suggestion_limit', 'favorites_active', 'favorites_max_custom_lists', 'favorites_max_products', 'comparison_active', 'comparison_max_products', 'comparison_max_saved_lists']) {
    await expect(page.locator(`#jform_${id}`)).toHaveCount(1);
  }
  const switcher = page.locator('#jform_search_category_active');
  const original = await switcher.locator('input:checked').getAttribute('value');
  const next = original === '1' ? '0' : '1';
  await page.locator(`label[for="${await switcher.locator(`input[value="${next}"]`).getAttribute('id')}"]`).click();
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForLoadState('domcontentloaded');
  await page.getByRole('tab', { name: 'Frontend-Funktionen', exact: true }).click();
  await expect(page.locator(`#jform_search_category_active input[value="${next}"]`)).toBeChecked();
  await page.locator(`label[for="${await page.locator(`#jform_search_category_active input[value="${original}"]`).getAttribute('id')}"]`).click();
  await expect(page.locator(`#jform_search_category_active input[value="${original}"]`)).toBeChecked();
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  diagnostics.expectClean();
});

test('category search and favorites render the same central product-card structure', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await authenticateSiteUser(page);
  await page.goto('/index.php?option=com_fdshop&view=category&id=900010');
  const categoryCard = page.locator('.fdshop-card[data-product-id="900100"]');
  await expect(categoryCard).toBeVisible();
  const categoryStructure = await categoryCard.evaluate(card => Array.from(card.children, node => node.className));
  const favorite = categoryCard.locator('[data-fdshop-favorite]');
  if (await favorite.getAttribute('aria-pressed') !== 'true') {
    await favorite.click();
    await expect(favorite).toHaveAttribute('aria-pressed', 'true');
  }
  await page.goto('/index.php?option=com_fdshop&view=favorites');
  const favoriteCard = page.locator('.fdshop-card[data-product-id="900100"]');
  await expect(favoriteCard).toBeVisible();
  expect(await favoriteCard.evaluate(card => Array.from(card.children, node => node.className))).toEqual(categoryStructure);
  expect(await favoriteCard.evaluate(card => card.getBoundingClientRect().width)).toBeLessThanOrEqual(234);
  await expect(favoriteCard.locator('[data-fdshop-video]')).toBeVisible();
  await expect(favoriteCard.locator('[data-fdshop-purchase]')).toBeVisible();
  await expect(favoriteCard.locator('[data-fdshop-compare]')).toBeVisible();
  diagnostics.expectClean();
});
