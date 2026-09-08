const { test, expect } = require('@playwright/test');
const { installDiagnostics } = require('../support/browser');

async function openCategory(page) {
  const response = await page.goto('/batterien');
  expect(response?.status()).toBe(200);
  await expect(page.locator('.fdshop-category')).toHaveAttribute('data-fdshop-category', '900010');
}

test('menu category renders mapped visible products and complete cards', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await openCategory(page);
  await expect(page.locator('h1')).toHaveText('E2E Hauptkategorie');
  await expect(page.locator('.fdshop-category__description')).toContainText('Künstliche Hauptkategorie');
  await expect(page.locator('[data-fdshop-results]')).toHaveText('1–24 von 30');
  await expect(page.locator('.fdshop-card')).toHaveCount(24);
  const defaultNames = await page.locator('.fdshop-card__title').allTextContents();
  expect(defaultNames).toEqual([...defaultNames].sort((a, b) => a.localeCompare(b, 'de')));

  await expect(page.locator('[data-product-id="900101"]')).toHaveCount(0);
  await expect(page.locator('[data-product-id="900102"]')).toHaveCount(0);
  await expect(page.locator('[data-product-id="901023"]')).toHaveCount(0);
  await expect(page.locator('[data-product-id="901024"]')).toHaveCount(0);
  await expect(page.locator('[data-product-id="900109"]')).toHaveCount(0);

  const product = page.locator('[data-product-id="900100"]');
  await expect(product).toContainText('Verfügbar');
  await expect(product).toContainText('NEM');
  await expect(product).toContainText('125,5 g');
  await expect(product).toContainText('Schusszahl');
  await expect(product).toContainText('Kaliber');
  await expect(product).toContainText('Brenndauer');
  await expect(product).toContainText('Steighöhe');
  await expect(product.locator('.fdshop-ribbon')).toHaveCount(3);
  const detailLink = product.getByRole('link', { name: 'Details', exact: true });
  await expect(detailLink).toHaveAttribute('href', /\/batterien\//);
  const detailHref = await detailLink.getAttribute('href');
  await expect(product.locator('.fdshop-card__image-link')).toHaveAttribute('href', detailHref);
  await expect(product.locator('.fdshop-card__title a')).toHaveAttribute('href', detailHref);

  await expect(page.locator('[data-product-id="900103"] img')).toHaveAttribute('src', /e2e-fixture-product\.svg$/);
  await expect(page.locator('[data-product-id="900104"] img')).toHaveAttribute('src', /product-placeholder\.svg$/);
  for (const status of ['Verfügbar', 'wenige Verfügbar', 'Bestellbar', 'wenige Bestellbar', 'Ausverkauft']) {
    await expect(page.locator('.fdshop-stock', { hasText: status }).first()).toBeVisible();
  }
  await expect(page.locator('.fdshop-stock--normal', { hasText: 'Verfügbar' }).first()).toBeVisible();
  await expect(page.locator('.fdshop-stock--normal', { hasText: 'Bestellbar' }).first()).toBeVisible();
  await expect(page.locator('.fdshop-stock--low', { hasText: 'wenige Verfügbar' }).first()).toBeVisible();
  await expect(page.locator('.fdshop-stock--low', { hasText: 'wenige Bestellbar' }).first()).toBeVisible();
  await expect(page.locator('.fdshop-stock--none', { hasText: 'Ausverkauft' }).first()).toBeVisible();
  await expect(page.locator('.fdshop-category')).not.toContainText('Verfügbarkeit:');

  const discount = page.locator('[data-product-id="900105"]');
  await expect(discount.locator('[data-effective-price] strong')).toHaveText('39,99 EUR');
  await expect(discount.locator('.fdshop-card__regular-price')).toHaveText('50,00 EUR');
  await expect(page.locator('[data-product-id="900100"] [data-effective-price] strong')).toHaveText('19,99 EUR');
  await expect(page.locator('[data-product-id="900100"] [data-effective-price] small')).toHaveText('inkl. MwSt.');

  await expect(page.locator('iframe')).toHaveCount(0);
  await expect(product.getByRole('button', { name: 'Video' })).toBeVisible();
  await expect(page.locator('[data-product-id="900104"] button', { hasText: 'Video' })).toHaveCount(0);
  diagnostics.expectClean();
});

test('detail placeholder is reachable and card grid responds with four to one columns', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await openCategory(page);
  const detailUrl = await page.locator('[data-product-id="900100"] a', { hasText: 'Details' }).getAttribute('href');
  const response = await page.goto(detailUrl);
  expect(response?.status()).toBe(200);
  await expect(page.locator('.fdshop-product-placeholder h1')).toHaveText('E2E Produkt Aktiv');
  await expect(page.getByRole('link', { name: 'Zurück zur Kategorie' })).toBeVisible();

  await page.goto('/batterien');
  for (const [width, columns] of [[1400, 4], [1000, 3], [700, 2], [480, 1]]) {
    await page.setViewportSize({ width, height: 900 });
    const template = await page.locator('.fdshop-products').evaluate(element => getComputedStyle(element).gridTemplateColumns);
    expect(template.trim().split(/\s+/)).toHaveLength(columns);
  }
  diagnostics.expectClean();
});

test('sorting, limits, pagination and invalid inputs are server-side constrained', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.goto('/batterien?sort=name&dir=desc&limit=12');
  await expect(page.locator('.fdshop-card')).toHaveCount(12);
  const namesDesc = await page.locator('.fdshop-card__title').allTextContents();
  expect(namesDesc).toEqual([...namesDesc].sort((a, b) => b.localeCompare(a, 'de')));
  await expect(page.locator('[data-fdshop-results]')).toHaveText('1–12 von 30');

  for (const [limit, expected] of [[12, 12], [24, 24], [36, 30], [48, 30]]) {
    await page.goto(`/batterien?sort=name&dir=asc&limit=${limit}`);
    await expect(page.locator('[name="limit"]')).toHaveValue(String(limit));
    await expect(page.locator('.fdshop-card')).toHaveCount(expected);
  }

  await page.goto('/batterien?sort=price&dir=asc&limit=48');
  const pricesAsc = (await page.locator('.fdshop-card').evaluateAll(cards => cards.map(card => Number(card.dataset.price))));
  expect(pricesAsc).toEqual([...pricesAsc].sort((a, b) => a - b));
  await expect(page.locator('.fdshop-card')).toHaveCount(30);

  await page.goto('/batterien?sort=price&dir=desc&limit=24&limitstart=24');
  const pricesDesc = await page.locator('.fdshop-card').evaluateAll(cards => cards.map(card => Number(card.dataset.price)));
  expect(pricesDesc).toEqual([...pricesDesc].sort((a, b) => b - a));
  await expect(page.locator('.fdshop-card')).toHaveCount(6);
  await expect(page.locator('[data-fdshop-results]')).toHaveText('25–30 von 30');
  await expect(page.locator('.fdshop-pagination--top')).toBeVisible();
  await expect(page.locator('.fdshop-pagination--bottom')).toBeVisible();

  await page.goto('/batterien?sort=DROP_TABLE&dir=sideways&limit=999&limitstart=-5');
  await expect(page.locator('[data-fdshop-sort]')).toHaveValue('name:asc');
  await expect(page.locator('[name="limit"]')).toHaveValue('24');
  await expect(page.locator('[data-fdshop-results]')).toHaveText('1–24 von 30');
  diagnostics.expectClean();
});

test('video iframe is created only by user action and removed on close', async ({ page, baseURL }) => {
  const browserErrors = [];
  page.on('console', message => { if (message.type() === 'error') browserErrors.push(message.text()); });
  page.on('pageerror', error => browserErrors.push(error.message));
  await page.route('https://www.youtube-nocookie.com/**', route => route.fulfill({ status: 200, contentType: 'text/html', body: '<!doctype html><title>Video fixture</title>' }));
  await openCategory(page);
  const product = page.locator('[data-product-id="900100"]');
  await expect(page.locator('iframe')).toHaveCount(0);
  await product.getByRole('button', { name: 'Video' }).click();
  await expect(page.locator('[data-fdshop-video-dialog]')).toBeVisible();
  await expect(page.locator('iframe')).toHaveAttribute('src', 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ');
  await page.locator('[data-fdshop-video-close]').click();
  await expect(page.locator('iframe')).toHaveCount(0);
  expect(browserErrors).toEqual([]);
});
