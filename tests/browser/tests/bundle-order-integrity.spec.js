const { test, expect } = require('@playwright/test');
const { authenticateSiteUser, installDiagnostics } = require('../support/browser');

test('checkout keeps two overlapping bundles distinct and a repeated submission idempotent', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.route('https://i.ytimg.com/**', route => route.fulfill({status:200,contentType:'image/svg+xml',body:'<svg xmlns="http://www.w3.org/2000/svg"/>'}));
  await authenticateSiteUser(page);
  await page.goto('/index.php?option=com_fdshop&view=product&id=900100');
  const token = page.locator('[data-fdshop-bundle-token] input');
  const tokenName = await token.getAttribute('name'); const tokenValue = await token.getAttribute('value');
  const addBundle = async () => page.evaluate(async ({ tokenName, tokenValue }) => {
    const body = new FormData(); body.append(tokenName, tokenValue); body.append('bundle_id', '900400'); body.append('items', JSON.stringify({900100: 1, 900105: 1}));
    const response = await fetch('index.php?option=com_fdshop&format=json&task=bundle.addToCart', {method: 'POST', body});
    return {text: await response.text(), contentType: response.headers.get('content-type')};
  }, {tokenName, tokenValue});
  for (let index=0; index<2; index+=1) { const response=await addBundle(); const payload=JSON.parse(response.text); expect(payload.success, payload.message).toBe(true); }
  await page.goto('/warenkorb');
  const cart=page.locator('[data-fdshop-cart]'); await expect(cart.locator('[data-cart-bundle]')).toHaveCount(2);
  await cart.getByRole('button', {name:'ändern'}).first().click(); await cart.locator('[data-cart-select-shipment="900600"]').click();
  await cart.getByRole('button', {name:'ändern'}).nth(1).click(); await cart.locator('[data-cart-select-payment="900610"]').click();
  await cart.locator('[data-cart-terms]').check();
  const submissionId=await cart.locator('[data-cart-submission]').inputValue(); const checkoutToken=cart.locator('[data-cart-token] input'); const checkoutTokenName=await checkoutToken.getAttribute('name'); const checkoutTokenValue=await checkoutToken.getAttribute('value');
  const firstResponse=await page.evaluate(async ({checkoutTokenName,checkoutTokenValue,submissionId})=>{const body=new FormData();body.append(checkoutTokenName,checkoutTokenValue);body.append('order_note','');body.append('terms_accepted','1');body.append('submission_id',submissionId);const response=await fetch('index.php?option=com_fdshop&format=json&task=cart.checkout',{method:'POST',body});return response.text();},{checkoutTokenName,checkoutTokenValue,submissionId});
  expect(()=>JSON.parse(firstResponse)).not.toThrow(); const first=JSON.parse(firstResponse); expect(first.success,first.message).toBe(true); expect(first.data.already_processed).toBe(false);
  await page.goto(first.data.confirmation_url); await expect(page).toHaveURL(/view=checkoutconfirmation&order_number=/); await expect(page.locator('.fdshop-checkout-confirmation > ul > li').filter({has:page.locator('strong')})).toHaveCount(2); await expect(page.getByText('E2E Produkt Aktiv')).toHaveCount(3);
  const retry=await page.evaluate(async ({checkoutTokenName,checkoutTokenValue,submissionId})=>{const body=new FormData();body.append(checkoutTokenName,checkoutTokenValue);body.append('order_note','');body.append('terms_accepted','1');body.append('submission_id',submissionId);const response=await fetch('index.php?option=com_fdshop&format=json&task=cart.checkout',{method:'POST',body});return {text:await response.text(),contentType:response.headers.get('content-type')};},{checkoutTokenName,checkoutTokenValue,submissionId});
  const repeated=JSON.parse(retry.text); expect(repeated.success,repeated.message).toBe(true); expect(repeated.data.already_processed).toBe(true); expect(repeated.data.order_id).toBe(first.data.order_id);
  diagnostics.expectClean();
});
