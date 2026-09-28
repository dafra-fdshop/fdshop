const { test, expect } = require('@playwright/test');
const { authenticateAdministrator, authenticateSiteUser, installDiagnostics } = require('../support/browser');

test('email change requires a one-time confirmation link', async ({ page, baseURL }) => {
  await authenticateSiteUser(page);
  await page.goto('/index.php?option=com_fdshop&view=account&section=security');
  const address = `fdshop-account-${Date.now()}@example.test`;
  const before = await (await page.request.get('http://mailpit:8025/api/v1/messages')).json();
  const oldIds = new Set(before.messages.map(message => message.ID));
  await page.getByLabel('Neue E-Mail-Adresse').fill(address);
  await page.getByRole('button', { name: 'Bestätigung anfordern' }).click();
  await expect(page.getByText(/Bitte bestätigen Sie die neue Adresse/)).toBeVisible();

  const messages = await (await page.request.get('http://mailpit:8025/api/v1/messages')).json();
  const message = messages.messages.find(item => !oldIds.has(item.ID) && item.Subject === 'FDShop: neue E-Mail-Adresse bestätigen');
  expect(message).toBeTruthy();
  const detail = await (await page.request.get(`http://mailpit:8025/api/v1/message/${message.ID}`)).json();
  const match = String(detail.HTML || detail.Text || '').match(/href="([^"]*task=account\.verifyEmail[^"]*)"/);
  expect(match).toBeTruthy();
  const confirmation = new URL(match[1].replaceAll('&amp;', '&'));
  const sandbox = new URL(baseURL);
  confirmation.protocol = sandbox.protocol;
  confirmation.host = sandbox.host;
  await page.goto(confirmation.toString());
  await expect(page.getByText('Ihre neue E-Mail-Adresse wurde bestätigt.')).toBeVisible();
  await expect(page.getByText(address)).toBeVisible();
  await page.goto(confirmation.toString());
  await expect(page.getByText(/ungültig, abgelaufen oder wurde bereits verwendet/)).toBeVisible();
});

test('shipment request is deduplicated, leaves the order unchanged and can be resolved by admin', async ({ page, context, baseURL }, testInfo) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await authenticateSiteUser(page);
  await page.goto('/index.php?option=com_fdshop&view=account&section=orders&order_id=900800');
  await page.getByRole('button', { name: 'Abholstation ändern' }).click();
  await page.getByLabel('Gewünschte Station').selectOption('900603');
  await page.getByRole('button', { name: 'Anfrage verbindlich senden' }).click();
  await expect(page.getByText(/Änderungsanfrage wurde versendet/)).toBeVisible();
  await expect(page.getByText(/Änderungsanfrage zur Abholstation offen/)).toBeVisible();
  await expect(page.getByText(/E2E Versand Standard/).first()).toBeVisible();

  await authenticateAdministrator(page, context, testInfo);
  await page.goto('/administrator/index.php?option=com_fdshop&view=order&id=900800');
  await expect(page.locator('.card').filter({ hasText: 'Offene Abholstationsanfrage' }).getByText(/E2E Abholstation Alternativ/)).toBeVisible();
  await page.getByRole('button', { name: 'Als erledigt markieren' }).click();
  await expect(page.getByText(/als erledigt markiert/)).toBeVisible();
  diagnostics.expectClean();
});

test('withdrawal declaration is rejected only with an individual reason and remains separate from order status', async ({ page, context, baseURL }, testInfo) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await authenticateSiteUser(page);
  await page.goto('/index.php?option=com_fdshop&view=account&section=orders&order_id=900800');
  await page.getByRole('button', { name: 'Widerruf erklären' }).click();
  await expect(page.getByText(/Widerrufsfrist/)).toBeVisible();
  await page.getByRole('button', { name: 'Widerruf bestätigen' }).click();
  await expect(page.getByText(/Eingang Ihres Widerrufs wurde bestätigt/)).toBeVisible();
  await expect(page.getByText(/Widerruf: Eingegangen/)).toBeVisible();

  await authenticateAdministrator(page, context, testInfo);
  await page.goto('/administrator/index.php?option=com_fdshop&view=order&id=900800');
  await expect(page.getByText(/Status:\s*Eingegangen/)).toBeVisible();
  await page.getByRole('button', { name: 'Widerruf ablehnen' }).click();
  await expect(page.getByText(/individuelle Ablehnungsbegründung/)).toBeVisible();
  await page.getByLabel('Individuelle Ablehnungsbegründung').fill('E2E begründete Ablehnung');
  await page.getByRole('button', { name: 'Widerruf ablehnen' }).click();
  await expect(page.getByText(/Widerruf wurde abgelehnt/)).toBeVisible();
  await expect(page.getByText('E2E Bestellt').first()).toBeVisible();
  diagnostics.expectClean();
});

test('accepted withdrawal uses central cancellation workflow', async ({ page, context, baseURL }, testInfo) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await authenticateSiteUser(page);
  await page.goto('/index.php?option=com_fdshop&view=account&section=orders&order_id=900800');
  await page.getByRole('button', { name: 'Widerruf erklären' }).click();
  await page.getByRole('button', { name: 'Widerruf bestätigen' }).click();
  await expect(page.getByText(/Widerruf: Eingegangen/)).toBeVisible();
  await authenticateAdministrator(page, context, testInfo);
  await page.goto('/administrator/index.php?option=com_fdshop&view=order&id=900800');
  await page.getByRole('button', { name: 'Widerruf bestätigen' }).click();
  await expect(page.getByText(/Widerruf wurde akzeptiert/)).toBeVisible();
  await expect(page.getByText('Storniert', { exact: true }).first()).toBeVisible();
  await expect(page.getByText('available', { exact: true })).toBeVisible();
  diagnostics.expectClean();
});

test('standard customer can mail one F3 proof without permanent browser storage', async ({ page, context, baseURL }, testInfo) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await authenticateAdministrator(page, context, testInfo);
  await page.goto('/administrator/index.php?option=com_fdshop&view=order&id=900800');
  await page.getByRole('link', { name: 'Erika Mustermann' }).click();
  await page.getByRole('tab', { name: 'FDShop Käuferberechtigung' }).click();
  const buyerStatus=page.locator('#jform_fdshop_buyer_status');
  await buyerStatus.selectOption('standard');
  await page.getByRole('tab', { name: /FDShop customer details|FDShop Kundendaten/i }).click();
  await page.getByLabel(/First name|Vorname/i).fill('Erika');
  await page.getByLabel(/Last name|Nachname/i).fill('Mustermann');
  await page.getByLabel(/Street|Straße/i).fill('Teststraße 12');
  await page.getByLabel(/Postal code|PLZ/i).fill('12345');
  await page.getByLabel(/City|Ort/i).fill('Teststadt');
  await page.getByLabel(/Country|Land/i).fill('Deutschland');
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await expect(page.locator('#system-message-container')).toContainText(/saved|gespeichert/i);
  await authenticateSiteUser(page);
  await page.goto('/index.php?option=com_fdshop&view=account&section=f3');
  await expect(page.getByText('Keine aktive F3-Berechtigung')).toBeVisible();
  await page.getByLabel('F3-Schein').setInputFiles(require('node:path').join(__dirname, '../support/test-proof.pdf'));
  await page.getByRole('button', { name: 'Unterlagen zur Prüfung senden' }).click();
  await expect(page.getByText(/Unterlagen wurden sicher übermittelt und lokal gelöscht/)).toBeVisible();
  await expect(page.getByText(/Unterlagen am .* zur Prüfung übermittelt/)).toBeVisible();
  await expect(page.getByText('Keine aktive F3-Berechtigung')).toBeVisible();
  diagnostics.expectClean();
});
