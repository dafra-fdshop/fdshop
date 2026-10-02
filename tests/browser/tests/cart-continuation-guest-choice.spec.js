const { test, expect } = require('@playwright/test');

test('guest cart can explicitly replace an existing account cart without merging', async ({page}) => {
  await page.goto('/warenkorb');
  const cart = page.locator('[data-fdshop-cart]');
  const tokenName = await cart.locator('[data-cart-token] input').getAttribute('name');
  const added = await page.evaluate(async ({tokenName}) => {
    const body = new FormData(); body.append(tokenName, '1'); body.append('product_id', '900100'); body.append('quantity', '2');
    return (await fetch('index.php?option=com_fdshop&format=json&task=cart.add', {method: 'POST', body})).json();
  }, {tokenName});
  expect(added.success, added.message).toBe(true);
  await page.reload();
  await cart.getByRole('button', {name: 'Weiter zum Bestellen'}).click();
  const login = page.locator('.fdshop-account-entry__login form');
  await login.locator('input[name="username"]').fill(process.env.JOOMLA_ADMIN_USERNAME);
  await login.locator('input[name="password"]').fill(process.env.JOOMLA_ADMIN_PASSWORD);
  await login.getByRole('button', {name: /Log in|Anmelden/i}).click();
  const dialog = page.locator('[data-cart-conflict]');
  await expect(dialog).toBeVisible();
  const navigation = page.waitForNavigation({waitUntil: 'domcontentloaded'});
  await dialog.locator('[data-cart-conflict-choice="guest"]').click();
  await navigation;
  await expect(page.locator('[data-cart-conflict]')).toHaveCount(0);
  await expect(page.locator('[data-cart-item]')).toHaveCount(1);
  await expect(page.locator('[data-cart-quantity]')).toHaveValue('2');
});
