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
            hasReturned: () => false,
            resume: async () => {},
            start: async (options, orderPromise) => {
              window.__fdshopPayPalUserActivation = navigator.userActivation.isActive;
              window.__fdshopPayPalStartOptions = options;
              window.__fdshopPayPalOrder = await orderPromise;
              window.__fdshopPayPalStartCalls = (window.__fdshopPayPalStartCalls || 0) + 1;
              if (window.__fdshopPayPalStartCalls === 1) callbacks.onCancel({ orderId: window.__fdshopPayPalOrder.orderId });
              else callbacks.onError(new Error('simulated SDK error'));
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
  await orderButton.click();
  await expect(page.locator('[data-fdshop-cart-message]')).toContainText('nicht abschließen');
  expect(startRequests).toBe(2);
  expect(captureRequests).toBe(0);
  await expect(page.locator('[data-cart-item]')).toHaveCount(itemCountBeforePayment);
});

test('PayPal v6 redirect return resumes approval and captures exactly once', async ({ page }) => {
  let startRequests = 0;
  let captureRequests = 0;
  let captureBody = '';
  await page.route('**/*task=payment.start*', async route => {
    startRequests += 1;
    await route.fulfill({contentType:'application/json',body:JSON.stringify({success:true,data:{session_token:'redirect-session-token',order_id:'REDIRECT-ORDER-1',expires_at:new Date(Date.now()+600000).toISOString(),amount:'0.50',currency:'EUR'}})});
  });
  await page.route('**/*task=payment.capture*', async route => {
    captureRequests += 1;
    captureBody = route.request().postData() || '';
    await route.fulfill({contentType:'application/json',body:JSON.stringify({success:true,data:{confirmation_url:'about:blank'}})});
  });
  await page.route('https://www.sandbox.paypal.com/web-sdk/v6/core', route => route.fulfill({
    contentType: 'application/javascript',
    body: `window.paypal={createInstance:async()=>({findEligibleMethods:async()=>({isEligible:()=>true}),createPayPalOneTimePaymentSession:callbacks=>({hasReturned:()=>sessionStorage.getItem('__paypalRedirectOrder')!==null,resume:async()=>{const orderId=sessionStorage.getItem('__paypalRedirectOrder');sessionStorage.removeItem('__paypalRedirectOrder');await callbacks.onApprove({orderId});},start:async(options,orderPromise)=>{const order=await orderPromise;sessionStorage.setItem('__paypalRedirectOrder',order.orderId);window.location.reload();}})})};`,
  }));
  await authenticateSiteUser(page);
  await page.goto('/index.php?option=com_fdshop&view=product&id=900100&catid=900010');
  await page.locator('[data-purchase-submit]').click();
  await page.goto('/index.php?option=com_fdshop&view=cart');
  await page.getByRole('button', { name: 'ändern' }).nth(1).click();
  await page.getByRole('button', { name: /E2E PayPal Sandbox/ }).click();
  const terms = page.locator('[data-cart-terms]');
  if (await terms.isVisible()) await terms.check();
  await expect(page.locator('[data-cart-order]')).toBeEnabled();
  await page.locator('[data-cart-order]').click();
  await page.waitForURL('about:blank', {timeout:30_000});
  expect(startRequests).toBe(1);
  expect(captureRequests).toBe(1);
  expect(captureBody).toContain('name="session_token"');
  expect(captureBody).toContain('redirect-session-token');
  expect(captureBody).toContain('name="provider_order_id"');
  expect(captureBody).toContain('REDIRECT-ORDER-1');
});
