const { test, expect } = require('@playwright/test');
const { installDiagnostics } = require('../support/browser');

test('checkout registration survives activation, renders one login and returns the activated user to cart', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  const username = `fdshop_continue_${Date.now()}`;
  const email = `${username}@example.test`;
  const password = 'FDShop-Test-2026!';

  await page.goto('/warenkorb');
  const cart = page.locator('[data-fdshop-cart]');
  const tokenName = await cart.locator('[data-cart-token] input').getAttribute('name');
  const added = await page.evaluate(async ({ tokenName }) => {
    const body = new FormData();
    body.append(tokenName, '1');
    body.append('product_id', '900100');
    body.append('quantity', '1');
    return (await fetch('index.php?option=com_fdshop&format=json&task=cart.add', { method: 'POST', body })).json();
  }, { tokenName });
  expect(added.success, added.message).toBe(true);
  await page.reload();
  await cart.getByRole('button', { name: 'Weiter zum Bestellen' }).click();

  const form = page.locator('#member-registration');
  await form.getByLabel(/First name|Vorname/i).fill('Checkout');
  await form.getByLabel(/Last name|Nachname/i).fill('Aktivierung');
  await form.getByLabel(/Street and house number|Straße \+ Hausnummer/i).fill('Testweg 1');
  await form.getByLabel(/Postal code|PLZ/i).fill('12345');
  await form.getByLabel(/City|Ort/i).fill('Teststadt');
  await form.getByLabel(/Phone|Telefon/i).fill('+49 40 123456');
  await form.locator('#jform_username').fill(username);
  await form.locator('#jform_password1').fill(password);
  await form.locator('#jform_password2').fill(password);
  await form.locator('#jform_email1').fill(email);
  await form.getByRole('button', { name: /Registrieren|Register/i }).click();
  await expect(page.locator('.fdshop-registration-note--activation')).toHaveCount(0);
  await expect(page.locator('#system-message-container')).not.toBeEmpty();

  const messagesResponse = await page.request.get('http://mailpit:8025/api/v1/messages');
  expect(messagesResponse.ok()).toBe(true);
  const messages = await messagesResponse.json();
  const message = messages.messages.find(item => JSON.stringify(item.To || item.Recipients || item).includes(email));
  expect(message, `activation email for ${email}`).toBeTruthy();
  const detailResponse = await page.request.get(`http://mailpit:8025/api/v1/message/${message.ID}`);
  expect(detailResponse.ok()).toBe(true);
  const detail = await detailResponse.json();
  const content = String(detail.HTML || detail.Text || '');
  const href = (content.match(/href=["']([^"']*(?:registration\.activate|task=registration\.activate)[^"']*)["']/i)?.[1]
    || content.match(/https?:\/\/[^\s<>"']*(?:registration\.activate|task=registration\.activate)[^\s<>"']*/i)?.[0])
    ?.replaceAll('&amp;', '&');
  expect(href, 'Joomla activation link').toBeTruthy();
  const activationUrl = new URL(href, baseURL);
  activationUrl.protocol = new URL(baseURL).protocol;
  activationUrl.host = new URL(baseURL).host;
  await page.goto(activationUrl.toString());

  const componentLogin = page.locator('main form').filter({ has: page.locator('input[name="username"]') });
  await expect(componentLogin).toHaveCount(1);
  await expect(page.locator('.fdshop-account-entry')).toHaveCount(0);
  const login = componentLogin;
  await login.locator('input[name="username"]').fill(username);
  await login.locator('input[name="password"]').fill(password);
  await login.getByRole('button', { name: /Log in|Anmelden/i }).click();

  await expect(page).toHaveURL(/(?:option=com_fdshop.*view=cart|warenkorb)/);
  await expect(page.locator('[data-cart-conflict]')).toHaveCount(0);
  await expect(page.locator('[data-cart-item]')).toHaveCount(1);
  diagnostics.expectClean();
});

test('normal registration activation keeps Joomla original login and returns the user to account', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  const username = `fdshop_continue_normal_${Date.now()}`;
  const email = `${username}@example.test`;
  const password = 'FDShop-Test-2026!';

  await page.goto('/index.php?option=com_users&view=registration');
  await expect(page.getByRole('heading', { name: /Neu bei uns\?|New here\?/i })).toBeVisible();
  const form = page.locator('#member-registration');
  await form.getByLabel(/First name|Vorname/i).fill('Normal');
  await form.getByLabel(/Last name|Nachname/i).fill('Aktivierung');
  await form.getByLabel(/Street and house number|Straße \+ Hausnummer/i).fill('Testweg 2');
  await form.getByLabel(/Postal code|PLZ/i).fill('12345');
  await form.getByLabel(/City|Ort/i).fill('Teststadt');
  await form.getByLabel(/Phone|Telefon/i).fill('+49 40 654321');
  await form.locator('#jform_username').fill(username);
  await form.locator('#jform_password1').fill(password);
  await form.locator('#jform_password2').fill(password);
  await form.locator('#jform_email1').fill(email);
  await form.getByRole('button', { name: /Registrieren|Register/i }).click();
  await expect(page.locator('.fdshop-registration-note--activation')).toHaveCount(0);
  await expect(page.locator('#system-message-container')).not.toBeEmpty();

  const messages = await (await page.request.get('http://mailpit:8025/api/v1/messages')).json();
  const message = messages.messages.find(item => JSON.stringify(item.To || item.Recipients || item).includes(email));
  expect(message, `activation email for ${email}`).toBeTruthy();
  const detail = await (await page.request.get(`http://mailpit:8025/api/v1/message/${message.ID}`)).json();
  const content = String(detail.HTML || detail.Text || '');
  const href = (content.match(/href=["']([^"']*(?:registration\.activate|task=registration\.activate)[^"']*)["']/i)?.[1]
    || content.match(/https?:\/\/[^\s<>"']*(?:registration\.activate|task=registration\.activate)[^\s<>"']*/i)?.[0])
    ?.replaceAll('&amp;', '&');
  expect(href, 'Joomla activation link').toBeTruthy();
  const activationUrl = new URL(href, baseURL);
  activationUrl.protocol = new URL(baseURL).protocol;
  activationUrl.host = new URL(baseURL).host;
  await page.goto(activationUrl.toString());

  const login = page.locator('main form').filter({ has: page.locator('input[name="username"]') });
  await expect(login).toHaveCount(1);
  await expect(page.locator('.fdshop-account-entry')).toHaveCount(0);
  await expect(page.getByRole('main').getByRole('link', { name: /password|passwort/i })).toBeVisible();
  await expect(page.getByRole('main').getByRole('link', { name: /username|benutzername/i })).toBeVisible();
  await login.locator('input[name="username"]').fill(username);
  await login.locator('input[name="password"]').fill(password);
  await login.getByRole('button', { name: /Log in|Anmelden/i }).click();
  await expect(page).toHaveURL(/(?:option=com_fdshop.*view=account|component\/fdshop\/account|mein-konto)/);
  diagnostics.expectClean();
});
