const { test, expect } = require('@playwright/test');
const { installDiagnostics } = require('../support/browser');

test('checkout login resolves two carts only after an explicit responsive conflict choice', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.goto('/warenkorb');
  const cart = page.locator('[data-fdshop-cart]');
  const tokenName = await cart.locator('[data-cart-token] input').getAttribute('name');
  const added = await page.evaluate(async ({ tokenName }) => {
    const body = new FormData(); body.append(tokenName, '1'); body.append('product_id', '900100'); body.append('quantity', '2');
    return (await fetch('index.php?option=com_fdshop&format=json&task=cart.add', {method: 'POST', body})).json();
  }, {tokenName});
  expect(added.success, added.message).toBe(true);
  await page.reload();
  await cart.getByRole('button', {name: 'Weiter zum Bestellen'}).click();
  await expect(page.getByRole('heading', {name: /Fast geschafft!|Almost there!/})).toBeVisible();
  const continuationCookie = (await page.context().cookies()).find(cookie => cookie.name === 'fdshop_cart_continue');
  expect(continuationCookie).toMatchObject({httpOnly: true, sameSite: 'Lax', path: '/'});

  const username = process.env.JOOMLA_ADMIN_USERNAME;
  const password = process.env.JOOMLA_ADMIN_PASSWORD;
  const login = page.locator('.fdshop-account-entry__login form');
  await login.locator('input[name="username"]').fill(username);
  await login.locator('input[name="password"]').fill(password);
  await login.getByRole('button', {name: /Log in|Anmelden/i}).click();
  await expect(page).toHaveURL(/(?:option=com_fdshop.*view=cart|warenkorb)/);
  const dialog = page.locator('[data-cart-conflict]');
  await expect(dialog).toBeVisible();
  await expect(dialog.getByRole('heading', {name: 'Welchen Warenkorb möchtest du verwenden?'})).toBeVisible();
  await expect(dialog.locator('[data-cart-conflict-choice]')).toHaveCount(2);
  await expect(dialog).toContainText('Der nicht ausgewählte Warenkorb wird verworfen.');
  const rejected = await page.evaluate(async () => {
    const body = new FormData(); body.append('choice', 'guest');
    return (await fetch('index.php?option=com_fdshop&format=json&task=cart.chooseCart', {method: 'POST', body})).json();
  });
  expect(rejected.success).toBe(false);
  expect(rejected.message).toMatch(/Sicherheitstoken|security token/i);
  await expect(dialog).toBeVisible();

  await page.setViewportSize({width: 375, height: 800});
  await expect(dialog.locator('.fdshop-cart-conflict__choice ul').first()).toBeHidden();
  await dialog.locator('[data-cart-conflict-close]').click();
  await expect(page.locator('[data-cart-item]')).toHaveCount(3);
  await page.locator('[data-cart-conflict-open]').click();
  const choiceResponse = page.waitForResponse(response => response.url().includes('task=cart.chooseCart'));
  const choiceNavigation = page.waitForNavigation({waitUntil: 'domcontentloaded'});
  await dialog.locator('[data-cart-conflict-choice="user"]').click();
  expect((await choiceResponse).status()).toBe(200);
  await choiceNavigation;
  await expect(page).toHaveURL(/(?:option=com_fdshop.*view=cart|warenkorb)/);
  await expect(page.locator('[data-cart-conflict]')).toHaveCount(0);
  await expect(page.locator('[data-cart-item]')).toHaveCount(3);
  diagnostics.expectClean();
});
