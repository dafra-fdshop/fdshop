const { test, expect } = require('@playwright/test');
const { authenticateAdministrator, authenticateSiteUser, installDiagnostics, openView } = require('../support/browser');

const productId = 900106;
const productUrl = `/index.php?option=com_fdshop&view=product&id=${productId}&catid=900012`;
const editorView = `product&layout=edit&id=${productId}`;

test.setTimeout(120_000);

test('product editor keeps one valid form with and without active watchlist entries', async ({ page, context, baseURL }, testInfo) => {
  const diagnostics = await installDiagnostics(page, baseURL);

  await authenticateSiteUser(page);
  await page.goto(productUrl);
  await page.locator('[data-watch-open]').click();
  await page.getByRole('button', { name: /Benachrichtigung aktivieren|Activate notification/ }).click();
  await expect(page.locator('[data-watch-message]')).toContainText('aktiviert');

  await context.clearCookies();
  await authenticateAdministrator(page, context, testInfo);
  await openView(page, editorView);
  await page.getByRole('tab', { name: 'Lager' }).click();
  await expect(page.getByText(/Kunde\(n\) warten auf Verfügbarkeit/)).toBeVisible();
  await expect(page.locator('#adminForm form')).toHaveCount(0);
  await expect(page.locator('#adminForm input[name="task"]')).toHaveCount(1);
  await expect(page.locator('#adminForm input[type="hidden"][value="1"]')).not.toHaveCount(0);

  const originalName = await page.locator('#jform_product_name').inputValue();
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(new RegExp(`view=product.*layout=edit.*id=${productId}`));
  await expect(page.locator('#jform_id')).toHaveValue(String(productId));

  await page.getByRole('button', { name: 'Save & Close' }).click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(/view=products/);
  await openView(page, editorView);
  await page.locator('#jform_product_name').fill('UNSAVED WATCHLIST CHANGE');
  await page.getByRole('button', { name: 'Close', exact: true }).click();
  await page.waitForLoadState('networkidle');
  await openView(page, editorView);
  await expect(page.locator('#jform_product_name')).toHaveValue(originalName);

  await page.getByRole('tab', { name: 'Lager' }).click();
  await page.locator('#jform_stock_quantity').fill('5');
  const stockRadio = page.locator('input[name="jform[is_in_stock]"][value="1"]');
  await page.locator(`label[for="${await stockRadio.getAttribute('id')}"]`).click();
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForLoadState('networkidle');
  await page.getByRole('tab', { name: 'Lager' }).click();
  const notify = page.getByRole('button', { name: 'Kunden jetzt benachrichtigen' });
  await expect(notify).toBeEnabled();
  const rejected = await page.request.post('/administrator/index.php?option=com_fdshop&task=product.notifyWatchlist', {
    form: { id: String(productId) },
    maxRedirects: 0,
  });
  expect(rejected.status()).toBeGreaterThanOrEqual(400);
  await page.reload({ waitUntil: 'networkidle' });
  await page.getByRole('tab', { name: 'Lager' }).click();
  await expect(page.getByText(/Kunde\(n\) warten auf Verfügbarkeit/)).toBeVisible();
  await page.locator('#jform_product_name').fill('UNSAVED NOTIFY CHANGE');
  page.once('dialog', dialog => dialog.accept());
  await page.getByRole('button', { name: 'Kunden jetzt benachrichtigen' }).click();
  await page.waitForLoadState('networkidle');
  await expect(page.locator('#system-message-container')).toContainText('1 Benachrichtigung(en) versendet');
  await expect(page.locator('#jform_product_name')).toHaveValue(originalName);
  await page.getByRole('tab', { name: 'Lager' }).click();
  await expect(page.getByText('Keine aktiven Vormerkungen.')).toBeVisible();

  await openView(page, 'product&layout=edit&id=900100');
  await expect(page.locator('#adminForm form')).toHaveCount(0);
  await page.getByRole('button', { name: 'Save', exact: true }).click();
  await page.waitForLoadState('networkidle');
  await expect(page.locator('#jform_id')).toHaveValue('900100');
  await page.getByRole('button', { name: 'Save & Close' }).click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(/view=products/);
  await openView(page, 'product&layout=edit&id=900100');
  await page.getByRole('button', { name: 'Close', exact: true }).click();
  await page.waitForLoadState('networkidle');
  await expect(page).toHaveURL(/view=products/);
  diagnostics.expectClean();
});
