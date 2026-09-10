const { test, expect } = require('@playwright/test');

test('catalog mode hides purchase actions and rejects a forged direct add request', async ({ page }) => {
  await page.goto('/batterien');
  await expect(page.locator('[data-fdshop-purchase]')).toHaveCount(0);
  const tokenName = await page.locator('[data-purchase-token] input').getAttribute('name').catch(() => null);
  expect(tokenName).toBeNull();
  const login = await page.goto('/index.php?option=com_users&view=login');
  expect(login?.status()).toBe(200);
  const csrf = await page.locator('input[type="hidden"][value="1"]').first().getAttribute('name');
  const payload = await page.evaluate(async ({ csrf }) => {
    const body = new FormData(); body.append(csrf, '1'); body.append('product_id', '900100'); body.append('quantity', '1');
    return (await fetch('index.php?option=com_fdshop&format=json&task=cart.add', { method: 'POST', body })).json();
  }, { csrf });
  expect(payload.success).toBe(false);
  expect(payload.message).toContain('Katalogmodus');
});
