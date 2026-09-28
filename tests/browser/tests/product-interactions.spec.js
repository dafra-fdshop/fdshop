const { test, expect } = require('@playwright/test');
const { authenticateSiteUser, installDiagnostics } = require('../support/browser');

const soldOutUrl = '/index.php?option=com_fdshop&view=product&id=900106&catid=900012';
const productUrl = '/index.php?option=com_fdshop&view=product&id=900100&catid=900010';

test('sold-out purchase action offers guest guidance and an idempotent customer watchlist', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.goto(soldOutUrl);
  await expect(page.locator('[data-purchase-submit]')).toHaveCount(0);
  await page.locator('[data-watch-open]').click();
  const guestDialog = page.locator('[data-watch-dialog]');
  await expect(guestDialog).toContainText(/registrierten Kunden vorbehalten|registered customers/);
  await expect(guestDialog.getByRole('link', { name: /Anmelden|Log in/ })).toBeVisible();
  await expect(guestDialog.getByRole('link', { name: /Registrieren|Register/ })).toBeVisible();

  await authenticateSiteUser(page);
  await page.goto(soldOutUrl);
  await page.locator('[data-watch-open]').click();
  const activateResponse = page.waitForResponse(response => response.url().includes('task=interaction.watch'));
  await page.getByRole('button', { name: /Benachrichtigung aktivieren|Activate notification/ }).click();
  expect((await (await activateResponse).json()).success).toBe(true);
  await expect(page.locator('[data-watch-message]')).toContainText('aktiviert');
  await page.getByRole('button', { name: /Benachrichtigung aktivieren|Activate notification/ }).click();
  await expect(page.locator('[data-watch-message]')).toContainText('aktiviert');

  await page.goto('/index.php?option=com_fdshop&view=account&section=watchlist');
  await expect(page.getByRole('heading', { name: 'Verfügbarkeits-Watchlist' })).toBeVisible();
  await expect(page.getByText('E2E Produkt Ausverkauft')).toHaveCount(1);
  await page.getByRole('button', { name: 'Entfernen' }).click();
  await expect(page.getByText('Keine aktiven Vormerkungen')).toBeVisible();
  await page.goto(soldOutUrl);
  await page.getByRole('button', { name: /Stellen Sie eine Frage|Ask a question/ }).click();
  await expect(page.locator('[data-product-question-dialog]').getByLabel('Name')).not.toHaveValue('');
  await expect(page.locator('[data-product-question-dialog]').getByLabel(/E-Mail|Email/)).not.toHaveValue('');
  diagnostics.expectClean();
});

test('product question uses server product data, Joomla captcha fallback, honeypot and session rate limit', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.route('https://i.ytimg.com/**', route => route.fulfill({ status: 200, contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg"/>' }));
  const before = await (await page.request.get('http://mailpit:8025/api/v1/messages')).json();
  const beforeIds = new Set(before.messages.map(message => message.ID));

  const submitQuestion = async (question) => {
    await page.goto(productUrl);
    await page.getByRole('button', { name: /Stellen Sie eine Frage|Ask a question/ }).click();
    const dialog = page.locator('[data-product-question-dialog]');
    await dialog.getByLabel('Name').fill('E2E Fragender');
    await dialog.getByLabel(/E-Mail|Email/).fill('frage@example.test');
    await dialog.getByLabel(/Ihre Frage|Your question/).fill(question);
    await dialog.getByRole('button', { name: /Frage senden|Send question/ }).click();
  };

  await submitQuestion('Ist dieses Produkt für den geplanten Termin geeignet?');
  await expect(page.locator('#system-message-container')).toContainText('Produktfrage wurde versendet');
  const after = await (await page.request.get('http://mailpit:8025/api/v1/messages')).json();
  const message = after.messages.find(item => !beforeIds.has(item.ID) && item.Subject === 'Produktfrage: E2E Produkt Aktiv');
  expect(message).toBeTruthy();
  const detail = await (await page.request.get(`http://mailpit:8025/api/v1/message/${message.ID}`)).json();
  expect(detail.HTML).toContain('E2E-PROD-ACTIVE');
  expect(detail.HTML).toContain('E2E Produkt Aktiv');

  await page.goto(productUrl);
  await page.getByRole('button', { name: /Stellen Sie eine Frage|Ask a question/ }).click();
  const dialog = page.locator('[data-product-question-dialog]');
  await dialog.getByLabel('Name').fill('Bot');
  await dialog.getByLabel(/E-Mail|Email/).fill('bot@example.test');
  await dialog.getByLabel(/Ihre Frage|Your question/).fill('Dies ist eine ausreichend lange Bot-Frage.');
  await dialog.locator('[name="jform[website]"]').evaluate(input => { input.value = 'spam'; });
  await dialog.getByRole('button', { name: /Frage senden|Send question/ }).click();
  await expect(page.locator('#system-message-container')).toContainText('konnte nicht verarbeitet werden');

  await submitQuestion('Zweite reguläre Frage mit genügend Zeichen.');
  await submitQuestion('Dritte reguläre Frage mit genügend Zeichen.');
  await submitQuestion('Vierte reguläre Frage mit genügend Zeichen.');
  await expect(page.locator('#system-message-container')).toContainText('Bitte warten Sie');
  diagnostics.expectClean();
});

test('configured Joomla captcha provider renders generically and rejects an omitted solution', async ({ page }) => {
  test.skip(process.env.FDSHOP_EXPECT_CAPTCHA !== '1', 'Run explicitly with a configured Joomla CAPTCHA provider.');
  await page.route('https://i.ytimg.com/**', route => route.fulfill({ status: 200, contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg"/>' }));
  await page.goto(productUrl);
  await page.getByRole('button', { name: /Stellen Sie eine Frage|Ask a question/ }).click();
  const dialog = page.locator('[data-product-question-dialog]');
  await expect(dialog.locator('altcha-widget[name="jform[captcha]"]')).toBeVisible();
  const response = await dialog.locator('form').evaluate(async form => {
    const body = new FormData(form);
    body.set('jform[name]', 'CAPTCHA Test'); body.set('jform[email]', 'captcha@example.test'); body.set('jform[question]', 'Diese Frage hat absichtlich keine CAPTCHA-Lösung.');
    const result = await fetch(form.action, { method: 'POST', body });
    return { url: result.url, html: await result.text() };
  });
  expect(response.url).toMatch(/(?:view=product|\/900100)/);
  expect(response.html).toMatch(/CAPTCHA|Sicherheitscode|valid/i);
});
