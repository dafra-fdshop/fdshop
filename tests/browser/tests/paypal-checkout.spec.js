const { test, expect } = require('@playwright/test');
const { authenticateSiteUser } = require('../support/browser');

test.setTimeout(120_000);

test('PayPal v6 checkout creates a server order session and keeps the cart on cancel', async ({ page }) => {
  let startRequests = 0;
  let captureRequests = 0;
  page.on('request', request => {
    if (request.url().includes('task=payment.start')) startRequests += 1;
    if (request.url().includes('task=payment.capture')) captureRequests += 1;
  });
  await page.route('https://www.sandbox.paypal.com/web-sdk/v6/core', route => route.fulfill({
    contentType: 'application/javascript',
    body: `window.paypal = {
      createInstance: async (options) => {
        window.__fdshopPayPalAuth = options;
        await new Promise(resolve => { window.__fdshopResolvePayPalSdk = resolve; });
        return {
          findEligibleMethods: async () => ({ isEligible: () => true }),
          createPayPalOneTimePaymentSession: callbacks => ({
            start: async (options, orderPromise) => {
              window.__fdshopPayPalUserActivation = navigator.userActivation.isActive;
              window.__fdshopPayPalStartOptions = options;
              window.__fdshopPayPalOrder = await orderPromise;
              callbacks.onCancel({ orderId: window.__fdshopPayPalOrder.orderId });
            },
          }),
        };
      },
    };`,
  }));
  await authenticateSiteUser(page);
  await page.goto('/index.php?option=com_fdshop&view=product&id=900100&catid=900010');
  await page.locator('[data-purchase-submit]').click();
  await page.goto('/index.php?option=com_fdshop&view=cart');
  await page.getByRole('button', { name: 'ändern' }).nth(1).click();
  await page.getByRole('button', { name: /E2E PayPal Sandbox/ }).click();
  const terms = page.locator('[data-cart-terms]');
  if (await terms.isVisible()) await terms.check();
  const itemCountBeforePayment = await page.locator('[data-cart-item]').count();
  const orderButton = page.locator('[data-cart-order]');
  await expect(orderButton).toBeDisabled();
  await expect(orderButton).toHaveAttribute('aria-busy', 'true');
  expect(startRequests).toBe(0);
  await page.evaluate(() => window.__fdshopResolvePayPalSdk());
  await expect(orderButton).toBeEnabled();
  await orderButton.click();
  await expect(page.locator('[data-paypal-progress]')).toBeVisible({ timeout: 30_000 });
  await expect(page.locator('[data-paypal-countdown]')).toHaveText(/\d{2}:\d{2}/);
  await expect(page.locator('[data-fdshop-cart-message]')).toContainText('abgebrochen');
  expect(await page.evaluate(() => window.__fdshopPayPalOrder?.orderId || '')).not.toBe('');
  expect(startRequests).toBe(1);
  expect(captureRequests).toBe(0);
  expect(await page.evaluate(() => window.__fdshopPayPalUserActivation)).toBe(true);
  expect(await page.evaluate(() => window.__fdshopPayPalStartOptions)).toEqual({ presentationMode: 'auto' });
  expect(await page.evaluate(() => ({clientId: Boolean(window.__fdshopPayPalAuth?.clientId), clientToken: Boolean(window.__fdshopPayPalAuth?.clientToken), components: window.__fdshopPayPalAuth?.components}))).toEqual({clientId: true, clientToken: false, components: ['paypal-payments']});
  await expect(page.locator('[data-cart-item]')).toHaveCount(itemCountBeforePayment);
});
