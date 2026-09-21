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
  await expect(product).toContainText('125,5 g');
  await expect(product.locator('.fdshop-card__fact')).toHaveCount(5);
  await expect(product.locator('.fdshop-card__fact img')).toHaveCount(5);
  expect(await product.locator('.fdshop-card__fact').evaluateAll(items => items.map(item => item.title)))
    .toEqual(['NEM', 'Schusszahl', 'Kaliber', 'Brenndauer', 'Steighöhe']);
  await expect(page.locator('[data-product-id="901001"] .fdshop-card__fact dd')).toHaveText(['-', '-', '-', '-', '-']);
  await expect(product.locator('.fdshop-ribbon')).toHaveCount(3);
  await expect(product).toHaveAttribute('data-visual-state', 'new');
  await expect(product.locator('.fdshop-card__visual')).toHaveClass(/fdshop-product-visual--new/);
  await expect(product.locator('.fdshop-card__visual')).toHaveCSS('background-color', 'rgb(0, 0, 0)');
  await expect(product.locator('.fdshop-card__visual')).toHaveCSS('background-repeat', 'no-repeat');
  expect(await product.locator('.fdshop-card__visual').evaluate(element => getComputedStyle(element).backgroundImage)).toContain('category-new.webp');
  await expect(page.locator('[data-product-id="900104"]')).toHaveAttribute('data-visual-state', 'standard');
  await expect(page.locator('[data-product-id="900105"]')).toHaveAttribute('data-visual-state', 'action');
  await expect(product).toHaveCSS('border-top-width', '4px');
  await expect(product).toHaveCSS('border-top-color', 'rgb(173, 181, 189)');
  await expect(product).toHaveCSS('background-color', 'rgb(255, 255, 255)');
  const detailLink = product.getByRole('link', { name: 'Details', exact: true });
  await expect(detailLink).toHaveAttribute('href', /\/batterien\//);
  const detailHref = await detailLink.getAttribute('href');
  await expect(product.locator('.fdshop-card__image-link')).toHaveAttribute('href', detailHref);
  await expect(product.locator('.fdshop-card__title a')).toHaveAttribute('href', detailHref);

  await expect(page.locator('[data-product-id="900103"] .fdshop-card__media img')).toHaveAttribute('src', /e2e-fixture-product\.svg$/);
  await expect(page.locator('[data-product-id="900103"] .fdshop-card__product-image')).toHaveCount(1);
  await expect(page.locator('[data-product-id="900104"] .fdshop-card__media img')).toHaveAttribute('src', /product-placeholder\.svg$/);
  const placeholderLayout = await page.locator('[data-product-id="900104"] .fdshop-card__media').evaluate(element => {
    const stage = element.getBoundingClientRect();
    const image = element.querySelector('.fdshop-card__placeholder').getBoundingClientRect();
    return {
      widthRatio: image.width / stage.width,
      heightRatio: image.height / stage.height,
      centeredX: Math.abs((image.left + image.width / 2) - (stage.left + stage.width / 2)) < 2,
      bottomAligned: Math.abs(image.bottom - stage.bottom) < 2,
    };
  });
  expect(placeholderLayout.widthRatio).toBeLessThan(0.5);
  expect(placeholderLayout.heightRatio).toBeLessThan(0.5);
  expect(placeholderLayout.centeredX).toBe(true);
  expect(placeholderLayout.bottomAligned).toBe(true);
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
  await expect(discount.locator('[data-effective-price] strong')).toHaveCSS('color', 'rgb(224, 167, 33)');
  await expect(discount.locator('.fdshop-card__regular-price')).toHaveText('50,00 EUR');
  await expect(discount.locator('.fdshop-card__regular-price')).toHaveCSS('color', 'rgb(220, 53, 69)');
  await expect(discount.locator('.fdshop-card__regular-price')).toHaveCSS('text-decoration-line', 'line-through');
  await expect(page.locator('[data-product-id="900100"] [data-effective-price] strong')).toHaveText('19,99 EUR');
  await expect(page.locator('[data-product-id="900100"] [data-effective-price] strong')).toHaveCSS('color', 'rgb(224, 167, 33)');
  await expect(page.locator('[data-product-id="900100"] [data-effective-price] small')).toHaveText('inkl. MwSt.');

  await expect(page.locator('iframe')).toHaveCount(0);
  const videoButton = product.getByRole('button', { name: 'Produktvideo zu E2E Produkt Aktiv ansehen' });
  await expect(videoButton).toBeVisible();
  await expect(videoButton).toHaveText('');
  await expect(videoButton.locator('.fa-solid.fa-video')).toHaveCount(1);
  await expect(product.locator('.fdshop-card__actions > .fdshop-stock')).toHaveCount(1);
  const defaultActionLayout = await product.locator('.fdshop-card__actions').evaluate(element => {
    const children = [...element.children].map(child => child.getBoundingClientRect());
    const actions = element.getBoundingClientRect();
    return {
      rows: new Set(children.map(child => Math.round(child.top))).size,
      statusRight: Math.abs(children.at(-1).right - actions.right) < 2,
      noOverflow: element.scrollWidth <= element.clientWidth,
    };
  });
  expect(defaultActionLayout.rows).toBeLessThanOrEqual(2);
  expect(defaultActionLayout.statusRight).toBe(true);
  expect(defaultActionLayout.noOverflow).toBe(true);
  const productWithoutVideo = page.locator('[data-product-id="900104"]');
  await expect(productWithoutVideo.locator('.fdshop-card__actions button')).toHaveCount(0);
  await expect(productWithoutVideo.locator('.fdshop-card__actions')).toHaveCount(1);
  await expect(productWithoutVideo.locator('.fdshop-card__actions > *')).toHaveCount(2);
  diagnostics.expectClean();
});

test('manual category card CSS refinements remain responsive', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);

  for (const [width, expectedHeight] of [[1440, '168px'], [768, '168px'], [390, '288px']]) {
    await page.setViewportSize({ width, height: 900 });
    await openCategory(page);
    const card = page.locator('[data-product-id="900100"]');
    await card.locator('.fdshop-card__title a').evaluate(element => {
      element.textContent = 'Ein außergewöhnlich langer Produkttitel zur sicheren Ellipsis-Prüfung';
    });
    await card.locator('.fdshop-card__description').evaluate(element => {
      element.textContent = 'Eine bewusst sehr lange Kurzbeschreibung, die auch bei schmaleren Karten deutlich mehr als zwei sichtbare Textzeilen beanspruchen würde.';
    });
    const layout = await card.evaluate(element => {
      const media = element.querySelector('.fdshop-card__media');
      const link = element.querySelector('.fdshop-card__image-link');
      const title = element.querySelector('.fdshop-card__title');
      const description = element.querySelector('.fdshop-card__description');
      const lineHeight = Number.parseFloat(getComputedStyle(description).lineHeight);
      return {
        mediaHeight: getComputedStyle(media).height,
        imageAlignment: getComputedStyle(link).alignItems,
        titleWhiteSpace: getComputedStyle(title).whiteSpace,
        titleOverflow: getComputedStyle(title).overflow,
        titleEllipsis: getComputedStyle(title).textOverflow,
        titleClipped: title.scrollWidth > title.clientWidth,
        descriptionClamp: getComputedStyle(description).webkitLineClamp,
        descriptionOrient: getComputedStyle(description).webkitBoxOrient,
        descriptionOverflow: getComputedStyle(description).overflow,
        descriptionLines: description.getBoundingClientRect().height / lineHeight,
        noOverflow: element.scrollWidth <= element.clientWidth,
      };
    });
    expect(layout.mediaHeight).toBe(expectedHeight);
    expect(layout.imageAlignment).toBe('end');
    expect(layout.titleWhiteSpace).toBe('nowrap');
    expect(layout.titleOverflow).toBe('hidden');
    expect(layout.titleEllipsis).toBe('ellipsis');
    expect(layout.titleClipped).toBe(true);
    expect(layout.descriptionClamp).toBe('2');
    expect(layout.descriptionOrient).toBe('vertical');
    expect(layout.descriptionOverflow).toBe('hidden');
    expect(layout.descriptionLines).toBeLessThanOrEqual(2.1);
    expect(layout.noOverflow).toBe(true);
  }

  diagnostics.expectClean();
});

test('product detail is reachable and card grid responds with four to one columns', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.route('https://i.ytimg.com/**', route => route.fulfill({ status: 200, contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg"/>' }));
  await openCategory(page);
  const detailUrl = await page.locator('[data-product-id="900100"] a', { hasText: 'Details' }).getAttribute('href');
  const response = await page.goto(detailUrl);
  expect(response?.status()).toBe(200);
  await expect(page.locator('.fdshop-product h1')).toHaveText('E2E Produkt Aktiv');

  await page.goto('/batterien');
  for (const [width, columns] of [[1920, 4], [1440, 4], [1000, 3], [768, 2], [390, 1]]) {
    await page.setViewportSize({ width, height: 900 });
    const template = await page.locator('.fdshop-products').evaluate(element => getComputedStyle(element).gridTemplateColumns);
    expect(template.trim().split(/\s+/)).toHaveLength(columns);
    const factLayout = await page.locator('[data-product-id="900100"] .fdshop-card__facts').evaluate(element => ({
      columns: getComputedStyle(element).gridTemplateColumns.trim().split(/\s+/).length,
      rows: new Set([...element.children].map(item => Math.round(item.getBoundingClientRect().top))).size,
    }));
    expect(factLayout).toEqual({ columns: 5, rows: 1 });
    const actionLayout = await page.locator('[data-product-id="900100"] .fdshop-card__actions').evaluate(element => {
      const children = [...element.children].map(child => child.getBoundingClientRect());
      const actions = element.getBoundingClientRect();
      return {
        rows: new Set(children.map(child => Math.round(child.top))).size,
        statusRight: Math.abs(children.at(-1).right - actions.right) < 2,
        noOverflow: element.scrollWidth <= element.clientWidth,
      };
    });
    expect(actionLayout.rows).toBeLessThanOrEqual(2);
    expect(actionLayout.statusRight).toBe(true);
    expect(actionLayout.noOverflow).toBe(true);
  }
  diagnostics.expectClean();
});

test('product detail renders gallery, video, manufacturer and public product information', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.route('https://i.ytimg.com/**', route => route.fulfill({ status: 200, contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg"/>' }));
  await openCategory(page);
  const detailHref = await page.locator('[data-product-id="900100"] a', { hasText: 'Details' }).getAttribute('href');
  await page.goto(detailHref);
  const product = page.locator('.fdshop-product[data-product-id="900100"]');

  await expect(product.getByRole('heading', { level: 1 })).toHaveText('E2E Produkt Aktiv');
  await expect(product.locator('[data-fdshop-main-image]')).toHaveAttribute('src', /e2e-fixture-product\.svg$/);
  await expect(product.locator('[data-fdshop-thumbnail]')).toHaveCount(2);
  await expect(product.locator('[data-fdshop-main-stage]')).toHaveClass(/fdshop-product-visual--new/);
  await expect(product.locator('[data-fdshop-main-stage]')).toHaveClass(/is-primary/);
  expect(await product.locator('[data-fdshop-main-stage]').evaluate(element => getComputedStyle(element).backgroundImage)).toContain('product-new.webp');
  await product.getByRole('button', { name: 'Produktbild 2 anzeigen' }).click();
  await expect(product.locator('[data-fdshop-main-image]')).toHaveAttribute('src', /product-placeholder\.svg$/);
  await expect(product.locator('[data-fdshop-main-stage]')).not.toHaveClass(/is-primary/);
  await product.getByRole('button', { name: 'Produktbild 1 anzeigen' }).click();
  await expect(product.locator('[data-fdshop-main-stage]')).toHaveClass(/is-primary/);

  const manufacturer = product.getByRole('link', { name: 'E2E Hersteller Aktiv' });
  await expect(product.locator('.fdshop-product__manufacturer')).not.toContainText('Hersteller:');
  expect(await product.locator('.fdshop-product__heading').evaluate(element => {
    const heading = element.querySelector('h1').getBoundingClientRect();
    const manufacturerName = element.querySelector('.fdshop-product__manufacturer').getBoundingClientRect();
    const shortDescription = element.nextElementSibling.getBoundingClientRect();
    return {
      sameRow: Math.abs(heading.top - manufacturerName.top) < 2,
      manufacturerRight: manufacturerName.left > heading.left,
      descriptionBelow: shortDescription.top >= Math.max(heading.bottom, manufacturerName.bottom),
    };
  })).toEqual({ sameRow: true, manufacturerRight: true, descriptionBelow: true });
  await page.setViewportSize({ width: 480, height: 900 });
  expect(await product.locator('.fdshop-product__heading').evaluate(element => {
    const heading = element.querySelector('h1').getBoundingClientRect();
    const manufacturerName = element.querySelector('.fdshop-product__manufacturer').getBoundingClientRect();
    return manufacturerName.top >= heading.bottom && Math.abs(manufacturerName.left - heading.left) < 2;
  })).toBe(true);
  await page.setViewportSize({ width: 1280, height: 720 });
  await manufacturer.click();
  await expect(page.locator('.fdshop-manufacturer h1')).toHaveText('E2E Hersteller Aktiv');
  await page.goBack();

  await expect(product.locator('.fdshop-product__fact')).toHaveCount(5);
  await expect(product.locator('.fdshop-product__fact img')).toHaveCount(5);
  expect(await product.locator('.fdshop-product__facts').evaluate(element => ({
    columns: getComputedStyle(element).gridTemplateColumns.trim().split(/\s+/).length,
    rows: new Set([...element.children].map(item => Math.round(item.getBoundingClientRect().top))).size,
  }))).toEqual({ columns: 5, rows: 1 });
  await expect(product.locator('.fdshop-product__fact img').first()).toHaveCSS('width', '32px');
  await expect(product).toContainText('125,5 g');
  await expect(product.locator('.fdshop-stock')).toHaveText(/Verfügbar/);
  await expect(product.locator('.fdshop-product__stock-copy')).toContainText('LAGERBESTAND:');
  await expect(product.locator('.fdshop-product__stock-copy')).toContainText('Im Lager');
  await expect(product.locator('[data-effective-price] strong')).toHaveText('19,99 EUR');
  await expect(product.locator('[data-effective-price] strong')).toHaveCSS('color', 'rgb(224, 167, 33)');
  await expect(product.locator('.fdshop-product__price')).toHaveCSS('background-color', 'rgb(240, 244, 251)');
  await expect(product.locator('.fdshop-product__regular-price')).toHaveCount(0);
  await expect(product.locator('[data-effective-price] small')).toHaveText('inkl. MwSt.');
  await expect(product.locator('.fdshop-product__description')).toContainText('Aktiv mit Bestand');
  await expect(product.locator('iframe')).toHaveCount(0);
  await expect(product.locator('.fdshop-product__video-play img')).toHaveAttribute('src', /i\.ytimg\.com\/vi\/aqz-KE-bpKQ\/hqdefault\.jpg/);
  await expect(product.locator('[data-fdshop-detail-video]')).toHaveCount(2);
  await expect(product.locator('iframe')).toHaveCount(0);
  await page.route('https://www.youtube-nocookie.com/**', route => route.fulfill({ status: 200, contentType: 'text/html', body: '<!doctype html><title>Video fixture</title>' }));
  await product.getByRole('button', { name: 'Produktvideo 2 zu E2E Produkt Aktiv abspielen' }).click();
  await expect(product.locator('[data-fdshop-detail-video-dialog] iframe')).toHaveAttribute('src', 'https://www.youtube-nocookie.com/embed/M7lc1UVf-VE');
  await product.getByRole('button', { name: 'Video schließen' }).click();
  await expect(product.locator('iframe')).toHaveCount(0);
  await product.getByRole('button', { name: 'Produktvideo zu E2E Produkt Aktiv abspielen' }).click();
  await expect(product.locator('iframe')).toHaveAttribute('src', 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ');

  await expect(product.locator('[class*="rating"], [class*="review"]')).toHaveCount(0);
  await expect(product).not.toContainText(/Vorheriges Produkt|Nächstes Produkt|PDF|Drucken|Freund empfehlen|Frage zu diesem Produkt|SKU|GTIN|Gewicht|Länge|Breite/);
  diagnostics.expectClean();
});

test('product detail rejects unpublished products and handles fallback and discount cases', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  const unpublished = await page.request.get('/index.php?option=com_fdshop&view=product&id=900101&catid=900010');
  expect(unpublished.status()).toBe(404);

  await page.goto('/index.php?option=com_fdshop&view=product&id=900104&catid=900010');
  await expect(page.locator('[data-fdshop-main-image]')).toHaveAttribute('src', /product-placeholder\.svg$/);
  await expect(page.locator('[data-fdshop-thumbnail]')).toHaveCount(0);
  await expect(page.locator('[data-fdshop-product-video]')).toHaveCount(0);
  await expect(page.locator('.fdshop-product__stock-copy')).toContainText('Verfügbar ab 15.10.2026');

  await page.goto('/index.php?option=com_fdshop&view=product&id=900106&catid=900010');
  await expect(page.locator('.fdshop-product__stock-copy')).toContainText('Noch nicht im Lager');
  await expect(page.locator('.fdshop-stock')).toHaveText(/Ausverkauft/);

  await page.goto('/index.php?option=com_fdshop&view=product&id=900105&catid=900010');
  await expect(page.locator('[data-effective-price] strong')).toHaveText('39,99 EUR');
  await expect(page.locator('[data-effective-price] strong')).toHaveCSS('color', 'rgb(224, 167, 33)');
  await expect(page.locator('.fdshop-product__regular-price')).toHaveText('50,00 EUR');
  await expect(page.locator('.fdshop-product__regular-price')).toHaveCSS('color', 'rgb(220, 53, 69)');
  await expect(page.locator('.fdshop-product__regular-price')).toHaveCSS('text-decoration-line', 'line-through');
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
  await product.getByRole('button', { name: 'Produktvideo zu E2E Produkt Aktiv ansehen' }).click();
  await expect(page.locator('[data-fdshop-video-dialog]')).toBeVisible();
  await expect(page.locator('iframe')).toHaveAttribute('src', 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ');
  await page.locator('[data-fdshop-video-close]').click();
  await expect(page.locator('iframe')).toHaveCount(0);
  expect(browserErrors).toEqual([]);
});
