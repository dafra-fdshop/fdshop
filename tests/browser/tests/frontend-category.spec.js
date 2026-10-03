const { test, expect } = require('@playwright/test');
const { installDiagnostics } = require('../support/browser');

async function openCategory(page) {
  const response = await page.goto('/batterien');
  expect(response?.status()).toBe(200);
  await expect(page.locator('.fdshop-category')).toHaveAttribute('data-fdshop-category', '900010');
}

async function productStructuredData(page) {
  const blocks = page.locator('script[type="application/ld+json"]');
  const products = [];
  for (let index = 0; index < await blocks.count(); index += 1) {
    const value = JSON.parse(await blocks.nth(index).textContent());
    if (value['@type'] === 'Product') products.push(value);
  }
  expect(products).toHaveLength(1);
  return products[0];
}

test('menu category renders mapped visible products and complete cards', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await openCategory(page);
  await expect(page.locator('h1')).toHaveText('E2E Hauptkategorie');
  const categoryDescription = page.locator('.fdshop-category__description');
  await expect(categoryDescription).toContainText('Kategoriebeschreibung Zeile 1');
  expect(await categoryDescription.evaluate(element => element.innerHTML)).toContain('<br>');
  await expect(categoryDescription.locator('script')).toHaveCount(0);
  expect(await page.evaluate(() => window.categoryPlainTextFailed)).toBeUndefined();
  await expect(page.locator('[data-fdshop-results]')).toHaveText('1–24 von 53');
  await expect(page.locator('.fdshop-card')).toHaveCount(24);
  const defaultNames = await page.locator('.fdshop-card__title').allTextContents();
  expect(defaultNames).toEqual([...defaultNames].sort((a, b) => a.localeCompare(b, 'de')));

  await expect(page.locator('[data-product-id="900101"]')).toHaveCount(0);
  await expect(page.locator('[data-product-id="900102"]')).toHaveCount(0);
  await expect(page.locator('[data-product-id="901023"]')).toHaveCount(0);
  await expect(page.locator('[data-product-id="901024"]')).toHaveCount(0);
  await expect(page.locator('[data-product-id="900109"]')).toHaveCount(0);

  const product = page.locator('[data-product-id="900100"]');
  const cardDescription = product.locator('.fdshop-card__description');
  expect(await cardDescription.evaluate(element => element.innerHTML)).toContain('<br>');
  await expect(cardDescription.locator('script')).toHaveCount(0);
  expect(await page.evaluate(() => window.plainTextFailed)).toBeUndefined();
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
  await expect(discount.locator('.fdshop-card__ribbons .fdshop-ribbon')).toHaveText(['% Angebot', 'Bundle', 'DISPLAY']);
  await expect(discount.locator('.fdshop-ribbon--action')).toHaveCSS('background-color', 'rgb(227, 6, 19)');
  expect(await discount.locator('.fdshop-ribbon--action').evaluate(element => getComputedStyle(element).transform)).not.toBe('none');
  await expect(discount.locator('[data-effective-price] strong')).toHaveText('39,99 EUR');
  await expect(discount.locator('[data-effective-price] strong')).toHaveCSS('color', 'rgb(220, 13, 27)');
  await expect(discount.locator('.fdshop-card__regular-price')).toHaveText('50,00 EUR');
  await expect(discount.locator('.fdshop-card__regular-price')).toHaveCSS('color', 'rgb(17, 17, 17)');
  await expect(discount.locator('.fdshop-card__regular-price')).toHaveCSS('text-decoration-line', 'line-through');
  await expect(discount.locator('.fdshop-card__regular-price')).toHaveCSS('text-decoration-color', 'rgb(220, 13, 27)');
  await expect(page.locator('[data-product-id="900100"] .fdshop-ribbon--action')).toHaveCount(0);
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
    expect(layout.imageAlignment).toBe('flex-end');
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

test('category selects real responsive derivatives while detail keeps standard media', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.route('https://i.ytimg.com/**', route => route.fulfill({ status: 200, contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg"/>' }));
  const requested = [];
  page.on('request', request => {
    if (request.url().includes('e2e-media-')) requested.push(request.url());
  });

  await page.setViewportSize({ width: 390, height: 900 });
  await openCategory(page);
  const image = page.locator('[data-product-id="900100"] .fdshop-card__product-image');
  await image.scrollIntoViewIfNeeded();
  await expect.poll(() => image.evaluate(element => element.currentSrc)).toContain('e2e-media-mobile.svg');
  expect(requested.some(url => url.includes('e2e-media-mobile.svg'))).toBe(true);
  expect(requested.some(url => url.includes('e2e-media-small.svg'))).toBe(false);
  expect(requested.some(url => url.includes('e2e-media-standard.svg'))).toBe(false);

  requested.length = 0;
  await page.setViewportSize({ width: 768, height: 900 });
  await page.reload();
  await expect.poll(() => image.evaluate(element => element.currentSrc)).toContain('e2e-media-small.svg');
  expect(requested.some(url => url.includes('e2e-media-small.svg'))).toBe(true);
  expect(requested.some(url => url.includes('e2e-media-mobile.svg'))).toBe(false);

  const detailUrl = await page.locator('[data-product-id="900100"] a', { hasText: 'Details' }).getAttribute('href');
  await page.goto(detailUrl);
  await expect(page.locator('[data-fdshop-main-image]')).toHaveAttribute('src', /e2e-media-standard\.svg$/);
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

test('product detail polish keeps actions responsive and selects the exact bundle', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.route('https://i.ytimg.com/**', route => route.fulfill({ status: 200, contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg"/>' }));

  await page.goto('/index.php?option=com_fdshop&view=product&id=900103&catid=900011');
  const detail = page.locator('.fdshop-product');
  await expect(detail.locator(':scope > .fdshop-search')).toHaveCount(0);
  await expect(detail.locator('[data-fdshop-package-select]')).toHaveValue('piece');
  await expect(detail.locator('[data-fdshop-package-select]')).toHaveAccessibleName('Display auswählen');
  await expect(detail.locator('[data-fdshop-package-select] option')).toHaveText(['Einzelpackung', 'Display']);
  await expect(detail.getByText('Hier auswählen, wenn ihr ein Display wollt.')).toBeVisible();
  await detail.locator('[data-fdshop-package-select]').selectOption('package');
  await expect(detail.locator('[data-fdshop-package-name]')).toHaveText('E2E Produkt Bild Display');

  const question = detail.getByRole('button', { name: 'Frage stellen' });
  await question.click();
  await expect(detail.locator('[data-product-question-dialog]')).toBeVisible();
  await detail.locator('[data-product-question-close]').click();

  await page.goto('/index.php?option=com_fdshop&view=product&id=900107&catid=900010');
  await expect(page.locator('[data-fdshop-package-select]')).toHaveAccessibleName('Schinken auswählen');
  await expect(page.locator('[data-fdshop-package-select] option')).toHaveText(['Einzelpackung', 'Schinken']);
  await expect(page.getByText('Hier auswählen, wenn ihr einen Schinken wollt.')).toBeVisible();

  await page.goto('/index.php?option=com_fdshop&view=product&id=900104&catid=900011');
  await expect(page.locator('[data-fdshop-package-select] option')).toHaveText(['Einzelpackung', 'Display (-5%)']);
  await page.locator('[data-fdshop-package-select]').selectOption('package');
  await expect(page.locator('[data-fdshop-package-select]')).toHaveValue('package');

  await page.goto('/index.php?option=com_fdshop&view=product&id=900105&catid=900010');
  await expect(page.locator('[data-fdshop-package-select]')).toBeVisible();
  const bundleTrigger = page.getByRole('button', { name: 'Bundle erstellen' });
  await expect(bundleTrigger.locator('.fa-cubes-stacked')).toHaveCSS('color', 'rgb(255, 255, 255)');
  const bundleHelp = page.getByText('Hier auswählen, wenn ihr ein Bundle wollt.');
  await expect(bundleHelp).toBeVisible();
  const bundleRows = await Promise.all([bundleTrigger.boundingBox(), bundleHelp.boundingBox()]);
  expect(bundleRows[1].y).toBeGreaterThanOrEqual(bundleRows[0].y + bundleRows[0].height);
  const productPolish = await page.locator('.fdshop-product').evaluate(root => {
    const commerce = root.querySelector('.fdshop-product__commerce');
    const price = root.querySelector('.fdshop-product__price');
    const cart = commerce.querySelector('.fdshop-purchase__button');
    const action = root.querySelector('.fdshop-product__action-button');
    const packageControl = root.querySelector('.fdshop-product__package-control');
    const commerceBox = commerce.getBoundingClientRect();
    const cartBox = cart.getBoundingClientRect();
    return {
      commerceBackground: getComputedStyle(commerce).backgroundColor,
      priceBackground: getComputedStyle(price).backgroundColor,
      cartInsideCommerce: cartBox.left >= commerceBox.left && cartBox.right <= commerceBox.right,
      actionRadius: parseFloat(getComputedStyle(action).borderTopLeftRadius),
      packageColumns: getComputedStyle(packageControl).gridTemplateColumns,
    };
  });
  expect(productPolish.commerceBackground).not.toBe('rgba(0, 0, 0, 0)');
  expect(productPolish.priceBackground).toBe('rgba(0, 0, 0, 0)');
  expect(productPolish.cartInsideCommerce).toBe(true);
  expect(productPolish.actionRadius).toBeGreaterThan(0);
  expect(productPolish.packageColumns.split(' ').length).toBe(2);
  await bundleTrigger.click();
  const picker = page.locator('[data-fdshop-bundle-picker]');
  await expect(picker.getByRole('heading', { name: 'Bundle auswählen' })).toBeVisible();
  await expect(picker.getByRole('button', { name: 'E2E Bundle Aktiv' })).toBeVisible();
  await expect(picker.getByRole('button', { name: 'E2E Bundle Zweite Wahl' })).toBeVisible();
  await page.keyboard.press('Escape');
  await expect(bundleTrigger).toBeFocused();
  await bundleTrigger.click();
  await picker.getByRole('button', { name: 'E2E Bundle Zweite Wahl' }).click();
  await expect(page.locator('[data-fdshop-bundle-dialog]').getByRole('heading', { name: 'E2E Bundle Zweite Wahl' })).toBeVisible();
  await page.locator('[data-fdshop-bundle-close]').click();

  for (const width of [1024, 768, 390]) {
    await page.setViewportSize({ width, height: 900 });
    await page.goto('/index.php?option=com_fdshop&view=product&id=900105&catid=900010');
    const layout = await page.locator('.fdshop-product__overview').evaluate(root => {
      const stage = root.querySelector('.fdshop-product__main-image');
      const image = root.querySelector('[data-fdshop-main-image]');
      const actions = root.querySelector('.fdshop-product__action-zone');
      const stageBox = stage.getBoundingClientRect();
      const imageBox = image.getBoundingClientRect();
      return {
        stageAlignment: getComputedStyle(stage).alignItems,
        bottomGap: stageBox.bottom - imageBox.bottom,
        imageInsideStage: imageBox.top >= stageBox.top && imageBox.bottom <= stageBox.bottom,
        noActionOverflow: actions.scrollWidth <= actions.clientWidth + 1,
      };
    });
    expect(layout.stageAlignment).toBe('flex-end');
    expect(layout.bottomGap).toBeGreaterThan(0);
    expect(layout.bottomGap).toBeLessThan(20);
    expect(layout.imageInsideStage).toBe(true);
    expect(layout.noActionOverflow).toBe(true);
  }

  await page.goto('/index.php?option=com_fdshop&view=product&id=900106&catid=900010');
  await expect(page.locator('[data-fdshop-package-select]')).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'Bundle erstellen' })).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'Frage stellen' })).toBeVisible();
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
  await expect(product.locator('[data-fdshop-main-image]')).toHaveAttribute('src', /e2e-media-standard\.svg$/);
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
  await expect(product.locator('.fdshop-product__commerce')).toHaveCSS('background-color', 'rgb(240, 244, 251)');
  await expect(product.locator('.fdshop-product__price')).toHaveCSS('background-color', 'rgba(0, 0, 0, 0)');
  await expect(product.locator('.fdshop-product__regular-price')).toHaveCount(0);
  await expect(product.locator('[data-effective-price] small')).toHaveText('inkl. MwSt.');
  const shortDescription = product.locator('.fdshop-product__short-description');
  expect(await shortDescription.evaluate(element => element.innerHTML)).toContain('<br>');
  await expect(shortDescription.locator('script')).toHaveCount(0);
  const longDescription = product.locator('.fdshop-product__description');
  await expect(longDescription).toContainText('Langbeschreibung Zeile 2 <p>bleibt Text</p>');
  expect(await longDescription.evaluate(element => element.innerHTML)).toContain('<br>');
  await expect(longDescription.locator('p')).toHaveCount(0);
  expect(await page.evaluate(() => window.plainTextFailed)).toBeUndefined();
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

test('product detail emits one derived and valid Product Offer JSON-LD block', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.route('https://i.ytimg.com/**', route => route.fulfill({ status: 200, contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg"/>' }));
  await page.goto('/index.php?option=com_fdshop&view=product&id=900100&catid=900010');
  const structured = await productStructuredData(page);
  expect(structured).toMatchObject({
    '@context': 'https://schema.org',
    '@type': 'Product',
    name: 'E2E Produkt Aktiv',
    sku: 'E2E-PROD-ACTIVE',
    description: 'Kurzbeschreibung Zeile 1 Kurzbeschreibung Zeile 2',
    brand: { '@type': 'Brand', name: 'E2E Hersteller Aktiv' },
    offers: {
      '@type': 'Offer',
      price: 19.99,
      priceCurrency: 'EUR',
      availability: 'https://schema.org/InStock',
    },
  });
  expect(new URL(structured.url).origin).toBe(new URL(baseURL).origin);
  expect(structured.offers.url).toBe(structured.url);
  expect(new URL(structured.image).origin).toBe(new URL(baseURL).origin);
  expect(structured.image).toMatch(/e2e-media-standard\.svg$/);
  expect(structured.aggregateRating).toBeUndefined();
  expect(structured.review).toBeUndefined();
  expect(structured.gtin).toBeUndefined();
  await expect(page.locator('[data-effective-price] strong')).toHaveText('19,99 EUR');
  await expect(page.locator('.fdshop-stock')).toContainText('Verfügbar');

  await page.goto('/batterien');
  const productBlocks = await page.locator('script[type="application/ld+json"]').evaluateAll(blocks => blocks
    .map(block => { try { return JSON.parse(block.textContent); } catch { return null; } })
    .filter(value => value?.['@type'] === 'Product'));
  expect(productBlocks).toEqual([]);
  diagnostics.expectClean();
});

test('Product JSON-LD handles optional fields, discounts and stock states without invented data', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.route('https://i.ytimg.com/**', route => route.fulfill({ status: 200, contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg"/>' }));

  await page.goto('/index.php?option=com_fdshop&view=product&id=900104&catid=900010');
  const sparse = await productStructuredData(page);
  expect(sparse.brand).toBeUndefined();
  expect(sparse.image).toBeUndefined();
  expect(sparse.description).toBeUndefined();

  await page.goto('/index.php?option=com_fdshop&view=product&id=900105&catid=900010');
  const discount = await productStructuredData(page);
  expect(discount.offers.price).toBe(39.99);
  await expect(page.locator('[data-effective-price] strong')).toHaveText('39,99 EUR');

  await page.goto('/index.php?option=com_fdshop&view=product&id=900107&catid=900010');
  expect((await productStructuredData(page)).offers.availability).toBe('https://schema.org/InStock');

  await page.goto('/index.php?option=com_fdshop&view=product&id=900106&catid=900010');
  expect((await productStructuredData(page)).offers.availability).toBe('https://schema.org/OutOfStock');

  await page.goto('/index.php?option=com_fdshop&view=product&id=900108&catid=900010');
  const special = await productStructuredData(page);
  expect(special.name).toContain('"Anführungszeichen"');
  expect(special.name.length).toBeGreaterThan(80);
  expect(special.description).toBe('Glanz & Spaß');
  const productBlock = await page.locator('script[type="application/ld+json"]').evaluateAll(blocks => blocks
    .map(block => block.textContent).find(content => JSON.parse(content)['@type'] === 'Product'));
  expect(productBlock).not.toContain('</script>');
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

test('sorting, limits, pagination state and invalid inputs are server-side constrained', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.goto('/batterien?sort=name&dir=desc&limit=12');
  await expect(page.locator('.fdshop-card')).toHaveCount(12);
  const namesDesc = await page.locator('.fdshop-card__title').allTextContents();
  expect(namesDesc).toEqual([...namesDesc].sort((a, b) => b.localeCompare(a, 'de')));
  await expect(page.locator('[data-fdshop-results]')).toHaveText('1–12 von 53');

  for (const [limit, expected] of [[12, 12], [24, 24], [36, 36], [48, 48]]) {
    await page.goto(`/batterien?sort=name&dir=asc&limit=${limit}`);
    await expect(page.locator('[name="limit"]')).toHaveValue(String(limit));
    await expect(page.locator('.fdshop-card')).toHaveCount(expected);
  }

  await page.goto('/batterien?sort=price&dir=asc&limit=48');
  const pricesAsc = (await page.locator('.fdshop-card').evaluateAll(cards => cards.map(card => Number(card.dataset.price))));
  expect(pricesAsc).toEqual([...pricesAsc].sort((a, b) => a - b));
  await expect(page.locator('.fdshop-card')).toHaveCount(48);

  await page.goto('/batterien?sort=price&dir=desc&limit=24&limitstart=24');
  const pricesDesc = await page.locator('.fdshop-card').evaluateAll(cards => cards.map(card => Number(card.dataset.price)));
  expect(pricesDesc).toEqual([...pricesDesc].sort((a, b) => b - a));
  await expect(page.locator('.fdshop-card')).toHaveCount(24);
  await expect(page.locator('[data-fdshop-results]')).toHaveText('25–48 von 53');
  await expect(page.locator('.fdshop-pagination--top')).toBeVisible();
  await expect(page.locator('.fdshop-pagination--bottom')).toBeVisible();

  await page.goto('/batterien?sort=DROP_TABLE&dir=sideways&limit=999&limitstart=-5');
  await expect(page.locator('[data-fdshop-sort]')).toHaveValue('name:asc');
  await expect(page.locator('[name="limit"]')).toHaveValue('24');
  await expect(page.locator('[data-fdshop-results]')).toHaveText('1–24 von 53');
  diagnostics.expectClean();
});

test('pagination preserves the complete catalog state and state changes reset to page one', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  const pageTwo = page.locator('.fdshop-pagination--top a.page-link').filter({ hasText: '2' }).first();
  const categoryUrl = '/index.php?option=com_fdshop&view=category&id=900010';

  await page.goto(`${categoryUrl}&sort=name&dir=asc&limit=48`);
  const firstPageNames = await page.locator('.fdshop-card__title').allTextContents();
  await expect(pageTwo).toHaveAttribute('href', /limit=48/);
  await pageTwo.click();
  await expect(page.locator('[name="limit"]')).toHaveValue('48');
  await expect(page.locator('[data-fdshop-results]')).toHaveText('49–53 von 53');
  const allNames = firstPageNames.concat(await page.locator('.fdshop-card__title').allTextContents());
  expect(allNames).toEqual([...allNames].sort((a, b) => a.localeCompare(b, 'de')));

  const manufacturer = '900001';
  const manufacturerQuery = `fd_filter%5Bmanufacturer%5D%5B%5D=${manufacturer}`;
  await page.goto(`${categoryUrl}&${manufacturerQuery}&sort=name&dir=asc&limit=24`);
  const filteredTotal = Number((await page.locator('[data-fdshop-results]').textContent()).match(/von\s+(\d+)/)?.[1]);
  expect(filteredTotal).toBeGreaterThan(24);
  await expect(page.locator('.fdshop-filter-chip')).toContainText(['E2E Hersteller Aktiv']);
  await pageTwo.click();
  await expect(page.locator('.fdshop-filter-chip')).toContainText(['E2E Hersteller Aktiv']);
  await expect(page.locator('[data-fdshop-results]')).toContainText(`von ${filteredTotal}`);
  await expect(page).toHaveURL(/fd_filter\[manufacturer\]\[0\]=900001/);
  await expect(page.locator('[data-product-id="900103"]')).toHaveCount(0);

  await page.goto(`${categoryUrl}&${manufacturerQuery}&sort=name&dir=asc&limit=48`);
  await pageTwo.click();
  await expect(page.locator('[name="limit"]')).toHaveValue('48');
  await expect(page.locator('.fdshop-filter-chip')).toContainText(['E2E Hersteller Aktiv']);

  await page.goto(`${categoryUrl}&${manufacturerQuery}&fd_filter%5Bavailability%5D%5B%5D=available&sort=price&dir=desc&limit=12`);
  await expect(page.locator('.fdshop-filter-chip')).toHaveCount(2);
  const pricesOne = await page.locator('.fdshop-card').evaluateAll(cards => cards.map(card => Number(card.dataset.price)));
  await pageTwo.click();
  await expect(page.locator('.fdshop-filter-chip')).toHaveCount(2);
  await expect(page).toHaveURL(/sort=price/);
  await expect(page).toHaveURL(/dir=desc/);
  const pricesTwo = await page.locator('.fdshop-card').evaluateAll(cards => cards.map(card => Number(card.dataset.price)));
  expect(pricesOne.concat(pricesTwo)).toEqual([...pricesOne, ...pricesTwo].sort((a, b) => b - a));

  await page.locator('[name="limit"]').selectOption('24');
  await page.waitForLoadState('networkidle');
  await expect(page).not.toHaveURL(/(?:limitstart|start)=/);
  await expect(page.locator('[data-fdshop-results]')).toContainText(/^1–/);
  await expect(page.locator('.fdshop-filter-chip')).toHaveCount(2);

  await page.locator('[data-fdshop-filter-remove]').filter({ hasText: 'Auf Lager' }).click();
  await expect(page).not.toHaveURL(/(?:limitstart|start)=/);
  await expect(page.locator('[data-fdshop-results]')).toContainText(/^1–/);
  await expect(page.locator('.fdshop-filter-chip')).toHaveCount(1);
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

test('video actions survive filtered catalog replacements and catalog state changes', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.addInitScript(() => {
    window.fdshopVideoDialogOpenCount = 0;
    const showModal = HTMLDialogElement.prototype.showModal;
    HTMLDialogElement.prototype.showModal = function (...args) {
      if (this.matches('[data-fdshop-video-dialog]')) window.fdshopVideoDialogOpenCount += 1;
      return showModal.apply(this, args);
    };
  });
  await page.route('https://www.youtube-nocookie.com/**', route => route.fulfill({ status: 200, contentType: 'text/html', body: '<!doctype html><title>Video fixture</title>' }));
  await page.setViewportSize({ width: 1400, height: 1000 });
  await openCategory(page);

  const openVideo = async (productId, source) => {
    const before = await page.evaluate(() => window.fdshopVideoDialogOpenCount);
    const card = page.locator(`[data-product-id="${productId}"]`);
    await card.locator('[data-fdshop-video]').click();
    await expect(page.locator('[data-fdshop-video-dialog]')).toBeVisible();
    await expect(page.locator('[data-fdshop-video-dialog] iframe')).toHaveAttribute('src', source);
    expect(await page.evaluate(() => window.fdshopVideoDialogOpenCount)).toBe(before + 1);
    await page.locator('[data-fdshop-video-close]').click();
    await expect(page.locator('[data-fdshop-video-dialog] iframe')).toHaveCount(0);
  };

  await openVideo('900100', 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ');
  let panel = page.locator('[data-fdshop-filter-module] .fdshop-filter');
  await panel.getByRole('checkbox', { name: /E2E Hersteller Aktiv/ }).check();
  await expect(page.locator('.fdshop-category')).not.toHaveAttribute('aria-busy', 'true');
  await openVideo('900100', 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ');

  panel = page.locator('[data-fdshop-filter-module] .fdshop-filter');
  await panel.getByRole('checkbox', { name: '20 bis 40 s' }).check();
  await expect(page.locator('.fdshop-filter-chip')).toHaveCount(2);
  await openVideo('900100', 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ');

  await page.locator('[data-fdshop-filter-module] [data-fdshop-filter-reset]').click();
  await expect(page.locator('.fdshop-filter-chip')).toHaveCount(0);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }),
    page.locator('[data-fdshop-sort]').selectOption('price:asc'),
  ]);
  await openVideo('900100', 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }),
    page.locator('[name="limit"]').selectOption('12'),
  ]);
  await openVideo('900100', 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ');

  await page.goto('/batterien?sort=name&dir=asc&limit=24&limitstart=24');
  await openVideo('901030', 'https://www.youtube-nocookie.com/embed/M7lc1UVf-VE');
  await page.goto('/batterien?fd_filter%5Bmanufacturer%5D%5B%5D=900001&sort=name&dir=asc&limit=24&limitstart=24');
  await expect(page.locator('.fdshop-filter-chip')).toContainText(['E2E Hersteller Aktiv']);
  await openVideo('901030', 'https://www.youtube-nocookie.com/embed/M7lc1UVf-VE');

  await page.goto('/batterien');
  panel = page.locator('[data-fdshop-filter-module] .fdshop-filter');
  for (let update = 0; update < 3; update += 1) {
    await panel.getByRole('checkbox', { name: '20 bis 40 s' }).check();
    await expect(page.locator('.fdshop-filter-chip')).toHaveCount(1);
    await page.locator('[data-fdshop-filter-module] [data-fdshop-filter-reset]').click();
    await expect(page.locator('.fdshop-filter-chip')).toHaveCount(0);
    panel = page.locator('[data-fdshop-filter-module] .fdshop-filter');
  }
  await page.setViewportSize({ width: 390, height: 900 });
  await openVideo('900100', 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ');
  await expect(page.locator('[data-product-id="900104"] [data-fdshop-video]')).toHaveCount(0);
  diagnostics.expectClean();
});

test('purchase actions survive repeated AJAX card replacements without duplicate handlers', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  let addRequests = 0;
  await page.route(/index\.php\?option=com_fdshop&format=json&task=cart\.add/, async route => {
    addRequests += 1;
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ success: true, data: { purchase: {
        adjusted: false,
        message: 'Testprodukt wurde hinzugefügt.',
        productName: 'E2E Produkt Aktiv',
        effectiveQuantity: 1,
        unitPrice: '19,99 EUR',
        lineAmount: '19,99 EUR',
        cartUrl: '/warenkorb',
      } } }),
    });
  });
  await page.setViewportSize({ width: 1400, height: 1000 });
  await openCategory(page);

  const purchase = async () => {
    const action = page.locator('[data-product-id="900100"] [data-fdshop-purchase]');
    await action.hover();
    await expect(action).toHaveClass(/is-open/);
    const previous = addRequests;
    await action.locator('[data-purchase-submit]').click();
    await expect(page.locator('[data-purchase-modal]')).toBeVisible();
    expect(addRequests).toBe(previous + 1);
    await page.locator('[data-purchase-close]').first().click();
  };

  await purchase();
  let panel = page.locator('[data-fdshop-filter-module] .fdshop-filter');
  await panel.getByRole('checkbox', { name: '20 bis 40 s' }).check();
  await expect(page.locator('.fdshop-card')).toHaveCount(1);
  await purchase();

  for (let update = 0; update < 3; update += 1) {
    await page.locator('[data-fdshop-filter-module] [data-fdshop-filter-reset]').click();
    await expect(page.locator('.fdshop-filter-chip')).toHaveCount(0);
    panel = page.locator('[data-fdshop-filter-module] .fdshop-filter');
    await panel.getByRole('checkbox', { name: '20 bis 40 s' }).check();
    await expect(page.locator('.fdshop-card')).toHaveCount(1);
  }
  await purchase();
  expect(addRequests).toBe(3);

  await page.locator('[data-fdshop-filter-module] [data-fdshop-filter-reset]').click();
  await expect(page.locator('.fdshop-filter-chip')).toHaveCount(0);
  panel = page.locator('[data-fdshop-filter-module] .fdshop-filter');
  await panel.getByRole('checkbox', { name: /Nicht auf Lager/ }).check();
  const watchButton = page.locator('[data-product-id="900106"] [data-watch-open]');
  await expect(watchButton).toBeVisible();
  await watchButton.click();
  await expect(page.locator('[data-watch-dialog]')).toBeVisible();
  await page.locator('[data-watch-close]').first().click();
  diagnostics.expectClean();
});
