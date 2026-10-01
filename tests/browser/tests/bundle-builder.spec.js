const { test, expect } = require('@playwright/test');
const { authenticateSiteUser, installDiagnostics } = require('../support/browser');

async function openBuilder(page) {
  await page.goto('/index.php?option=com_fdshop&view=product&id=900100');
  await page.getByRole('button', { name: 'Bundle zusammenstellen' }).click();
  const dialog = page.locator('[data-fdshop-bundle-dialog]');
  await expect(dialog.getByRole('heading', { name: 'E2E Bundle Aktiv' })).toBeVisible();
  return dialog;
}

test('bundle experience loads, supports quick view, button/drag selection and discount progress', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.route('https://www.youtube-nocookie.com/**', route => route.fulfill({ status: 200, contentType: 'text/html', body: '<!doctype html><title>Bundle video fixture</title>' }));
  await page.route(/task=bundle\.builder/, async route => {
    await new Promise(resolve => setTimeout(resolve, 220));
    const response = await route.fetch();
    const payload = await response.json();
    const result = payload.data || payload;
    const activeProduct = result.products.find(product => Number(product.id) === 900100);
    activeProduct.video_url = 'https://www.youtube.com/watch?v=aqz-KE-bpKQ';
    activeProduct.product_name = 'Sehr langer E2E Produktname mit zusätzlicher Breite und sauberem Umbruch';
    result.rules = [
      { min_quantity: 1, discount_percent: 3 },
      { min_quantity: 2, discount_percent: 5 },
      { min_quantity: 3, discount_percent: 8 },
      { min_quantity: 4, discount_percent: 12 },
    ];
    await route.fulfill({ response, json: payload });
  });
  await page.goto('/index.php?option=com_fdshop&view=product&id=900100');
  await page.getByRole('button', { name: 'Bundle zusammenstellen' }).click();
  const dialog = page.locator('[data-fdshop-bundle-dialog]');
  await expect(dialog.locator('.fdshop-bundle__loader')).toBeAttached();
  await expect(dialog.getByRole('heading', { name: 'E2E Bundle Aktiv' })).toBeVisible();
  await expect(dialog.getByText('Stell dein Feuerwerk zusammen')).toBeVisible();
  await expect(dialog.getByText('(Auf Touch-Geräten das Produkt kurz gedrückt halten.)')).toBeVisible();
  await expect(dialog.locator('.fdshop-bundle__pool [data-bundle-quantity]')).toHaveCount(0);
  await expect(dialog.getByText('Maximal 2 Stück je Produkt')).toBeVisible();
  await expect(dialog.locator('.fdshop-bundle__promise')).toContainText('DEIN FEUERWERK. DEINE AUSWAHL.');
  await expect(dialog.locator('.fdshop-bundle__promise')).toContainText('Mindestens 2 verschiedene Produkte');
  await expect(dialog.locator('.fdshop-bundle__eyebrow')).toHaveCSS('color', 'rgb(224, 167, 33)');
  await expect(dialog.locator('.fdshop-bundle__chosen-top > .fdshop-bundle__progress')).toHaveCount(1);
  await expect(dialog.locator('.fdshop-bundle__progress-heading')).toContainText('MEIN RABATT');
  await expect(dialog.locator('.fdshop-bundle__promise')).toHaveCSS('border-left-width', '0px');
  const compactHeader = await dialog.locator('.fdshop-bundle__chosen-top').evaluate(node => {
    const heading = node.querySelector('.fdshop-bundle__chosen-heading').getBoundingClientRect();
    const progress = node.querySelector('.fdshop-bundle__progress').getBoundingClientRect();
    const track = node.querySelector('.fdshop-bundle__progress-track').getBoundingClientRect();
    const status = node.querySelector('[data-bundle-progress-status]').getBoundingClientRect();
    return { sameRow: Math.abs(heading.top - progress.top) < 12, statusGap: status.top - track.bottom };
  });
  expect(compactHeader.sameRow).toBe(true);
  expect(compactHeader.statusGap).toBeGreaterThan(16);
  await expect(dialog.locator('[data-bundle-progress-status]')).toHaveText('Noch kein Rabatt aktiv · Noch 1 Artikel bis 3 %');
  await expect(dialog.locator('[data-bundle-reset]')).toBeHidden();

  const activePoolCard = dialog.locator('[data-bundle-pool-product="900100"]');
  await expect(activePoolCard.locator('.fdshop-bundle__pool-name')).toHaveText('Sehr langer E2E Produktname mit zusätzlicher Breite und sauberem Umbruch');
  const poolLayout = await activePoolCard.evaluate(node => {
    const title = node.querySelector('.fdshop-bundle__pool-name').getBoundingClientRect();
    const actions = node.querySelector('.fdshop-bundle__pool-actions').getBoundingClientRect();
    return { actionsBelowTitle: actions.top >= title.bottom - 1, fits: node.scrollWidth <= node.clientWidth + 1 };
  });
  expect(poolLayout).toEqual({ actionsBelowTitle: true, fits: true });
  await activePoolCard.getByRole('button', { name: /Details zu Sehr langer E2E Produktname/ }).click();
  const quick = dialog.locator('[data-bundle-quick]');
  await expect(quick).toBeVisible();
  await expect(quick).toContainText('E2E-PROD-ACTIVE');
  await expect(quick).toContainText('Künstlich');
  await quick.getByRole('button', { name: 'Video ansehen' }).click();
  await expect(quick.locator('iframe')).toHaveAttribute('src', 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ');
  await quick.getByRole('button', { name: 'Produktdetails schließen' }).click();
  await expect(activePoolCard.getByRole('button', { name: /Details zu Sehr langer E2E Produktname/ })).toBeFocused();

  const discountPoolCard = dialog.locator('[data-bundle-pool-product="900105"]');
  await discountPoolCard.getByRole('button', { name: 'Details zu E2E Produkt Aktionspreis' }).click();
  await expect(quick.getByRole('button', { name: 'Video ansehen' })).toHaveCount(0);
  await quick.getByRole('button', { name: '+ Zum Bundle hinzufügen' }).click();
  await expect(dialog.locator('[data-bundle-chosen-product="900105"]')).toBeVisible();
  await dialog.locator('[data-bundle-chosen-product="900105"]').getByRole('button', { name: 'Entfernen' }).click();

  await activePoolCard.getByRole('button', { name: '+ Hinzufügen' }).click();
  await expect(activePoolCard.getByRole('button', { name: '+ Hinzufügen' })).toHaveCSS('background-color', 'rgb(224, 167, 33)');
  await expect(dialog.locator('[data-bundle-chosen-product="900100"]')).toBeVisible();
  await expect(dialog.locator('[data-bundle-progress-status]')).toHaveText('Noch kein Rabatt aktiv · Noch 1 Artikel bis 3 %');
  const firstRibbon = dialog.locator('[data-bundle-celebration]');
  await expect(firstRibbon).toContainText('3 % RABATT FREIGESCHALTET');
  await expect(firstRibbon).toHaveAttribute('style', /25%/);
  await expect(dialog.locator('[data-bundle-celebration-effects]')).toHaveAttribute('style', /25%/);
  const layers = await dialog.locator('.fdshop-bundle__progress-scale').evaluate(node => ({
    ribbon: Number(getComputedStyle(node.querySelector('[data-bundle-celebration]')).zIndex),
    stars: Number(getComputedStyle(node.querySelector('[data-bundle-celebration-effects]')).zIndex),
  }));
  expect(layers.stars).toBeGreaterThan(layers.ribbon);
  await expect(firstRibbon).toHaveClass(/is-transferring/, { timeout: 3600 });
  await expect(dialog.locator('[data-bundle-progress-status]')).toContainText('3 % Rabatt aktiv', { timeout: 4500 });
  await expect(dialog.locator('[data-bundle-message]')).toBeHidden();
  await expect(dialog.locator('[data-bundle-reset]')).toBeVisible();
  await dialog.locator('[data-bundle-pool-product="900105"]').dragTo(dialog.locator('[data-bundle-dropzone]'));
  await expect(dialog.locator('.fdshop-bundle__chosen-card')).toHaveCount(2);
  await expect(dialog.locator('[data-bundle-progress-status]')).toContainText('3 % Rabatt aktiv');
  await expect(dialog.locator('[data-bundle-celebration]')).toContainText('5 % RABATT FREIGESCHALTET');
  await expect(dialog.locator('[data-bundle-celebration]')).toHaveAttribute('style', /50%/);
  await expect(dialog.locator('[data-bundle-subtotal]')).toHaveText('59,98 €');
  await expect(dialog.locator('[data-bundle-discount]')).toHaveText('−3,00 €');
  await expect(dialog.locator('[data-bundle-total]')).toHaveText('56,98 €');
  await expect(dialog.locator('[data-bundle-saving-row]')).toBeVisible();

  const discount = dialog.locator('[data-bundle-chosen-product="900105"]');
  await discount.getByRole('button', { name: 'Entfernen' }).click();
  await expect(dialog.locator('[data-bundle-celebration]')).toBeHidden();
  await discountPoolCard.getByRole('button', { name: '+ Hinzufügen' }).click();
  await expect(dialog.locator('[data-bundle-celebration]')).toContainText('5 % RABATT FREIGESCHALTET');

  const first = dialog.locator('[data-bundle-chosen-product="900100"]');
  await first.getByRole('button', { name: /erhöhen/ }).click();
  await expect(first.locator('[data-bundle-quantity]')).toHaveValue('2');
  await expect(first.getByRole('button', { name: /erhöhen/ })).toBeDisabled();
  await expect(dialog.locator('[data-bundle-celebration]')).toContainText('8 % RABATT FREIGESCHALTET');
  await expect(dialog.locator('[data-bundle-celebration]')).toHaveAttribute('style', /75%/);
  await dialog.locator('[data-bundle-chosen-product="900105"]').getByRole('button', { name: /erhöhen/ }).click();
  const maximum = dialog.locator('[data-bundle-maximum-celebration]');
  await expect(maximum).toBeVisible();
  await expect(maximum).toContainText('GLÜCKWUNSCH!');
  await expect(maximum).toContainText('12 % RABATT FREIGESCHALTET');
  await expect(maximum.locator('[data-bundle-maximum-burst] i')).toHaveCount(72);
  await expect(maximum.locator('.is-streamer')).toHaveCount(12);
  await expect(maximum.locator('.is-star')).toHaveCount(12);
  await expect(maximum.locator('.is-confetti')).toHaveCount(48);
  await expect(maximum.locator('[data-bundle-maximum-burst="behind"] i')).toHaveCount(57);
  await expect(maximum.locator('[data-bundle-maximum-burst="front"] i')).toHaveCount(15);
  const maximumLayout = await maximum.evaluate(node => {
    const overlay = node.getBoundingClientRect();
    const hat = node.querySelector('.fdshop-bundle__party-hat').getBoundingClientRect();
    const panel = node.querySelector('.fdshop-bundle__maximum-copy').getBoundingClientRect();
    const behind = Number(getComputedStyle(node.querySelector('.is-behind')).zIndex);
    const panelLayer = Number(getComputedStyle(node.querySelector('.fdshop-bundle__maximum-copy')).zIndex);
    const front = Number(getComputedStyle(node.querySelector('.is-front')).zIndex);
    return {
      hatLowerLeft: hat.left < overlay.left + overlay.width * .3 && hat.top > overlay.top + overlay.height * .55,
      transparent: getComputedStyle(node).backgroundColor === 'rgba(0, 0, 0, 0)',
      layered: behind < panelLayer && panelLayer < front,
      panelInside: panel.left >= overlay.left && panel.right <= overlay.right,
    };
  });
  expect(maximumLayout).toEqual({ hatLowerLeft: true, transparent: true, layered: true, panelInside: true });
  await expect(dialog.locator('[data-bundle-chosen-product]')).toHaveCount(2);
  await expect(dialog.locator('[data-bundle-celebration]')).toBeHidden();
  const second = dialog.locator('[data-bundle-chosen-product="900105"]');
  await second.getByRole('button', { name: /reduzieren/ }).click();
  await expect(maximum).toBeHidden();
  await second.getByRole('button', { name: /erhöhen/ }).click();
  await expect(maximum).toBeVisible();
  await expect(maximum.locator('[data-bundle-maximum-burst] i')).toHaveCount(72);
  await expect(dialog.locator('[data-bundle-maximum-celebration]')).toHaveCount(1);
  await second.getByRole('button', { name: /reduzieren/ }).click();
  await second.getByRole('button', { name: /erhöhen/ }).click();
  await expect(maximum).toBeVisible();
  await expect(maximum.locator('[data-bundle-maximum-burst] i')).toHaveCount(72);
  await expect(dialog.locator('[data-bundle-progress-status]')).toContainText('12 % Rabatt aktiv', { timeout: 4300 });
  await second.getByRole('button', { name: /reduzieren/ }).click();
  await first.getByRole('button', { name: /reduzieren/ }).click();
  await first.getByRole('button', { name: 'Entfernen' }).click();
  await expect(dialog.locator('[data-bundle-chosen-product="900100"]')).toHaveCount(0);
  await dialog.getByRole('button', { name: 'Auswahl zurücksetzen' }).click();
  await expect(dialog.getByText('Stell dein Feuerwerk zusammen')).toBeVisible();
  await expect(dialog.locator('[data-bundle-reset]')).toBeHidden();
  await expect(dialog.getByRole('button', { name: 'In den Warenkorb' })).toBeDisabled();
  diagnostics.expectClean();
});

test('bundle cart request remains single and server snapshot totals stay authoritative', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  const dialog = await openBuilder(page);
  await dialog.locator('[data-bundle-pool-product="900100"] [data-bundle-add]').click();
  await dialog.locator('[data-bundle-pool-product="900105"] [data-bundle-add]').click();
  let addRequests = 0;
  page.on('request', request => { if (request.url().includes('task=bundle.addToCart')) addRequests += 1; });
  const addResponse = page.waitForResponse(response => response.url().includes('task=bundle.addToCart'));
  await dialog.getByRole('button', { name: 'In den Warenkorb' }).click();
  const payload = await (await addResponse).json();
  expect(payload.success, payload.message).toBe(true);
  await expect(page).toHaveURL(/view=cart|warenkorb/);
  expect(addRequests).toBe(1);
  const bundle = page.locator('[data-cart-bundle]');
  await expect(bundle).toContainText('E2E Bundle Aktiv');
  await expect(bundle).toContainText('56,98 €');
  await expect(bundle.locator('li')).toHaveCount(2);
  await expect(page.locator('[data-cart-subtotal]')).toHaveText('56,98 €');
  await bundle.getByRole('button', { name: 'Bundle entfernen' }).click();
  await expect(page.locator('[data-cart-bundle]')).toHaveCount(0);
  diagnostics.expectClean();
});

test('bundle endpoint rejects excess per-product quantity without partial cart write', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.goto('/index.php?option=com_fdshop&view=product&id=900100');
  const tokenName = await page.locator('[data-fdshop-bundle-token] input').getAttribute('name');
  const result = await page.evaluate(async ({ tokenName }) => {
    const body = new FormData(); body.append(tokenName, '1'); body.append('bundle_id', '900400'); body.append('items', JSON.stringify({900100: 3, 900105: 1}));
    return (await fetch('index.php?option=com_fdshop&format=json&task=bundle.addToCart', {method: 'POST', body})).json();
  }, { tokenName });
  expect(result.success).toBe(false);
  expect(result.message).toContain('maximale Anzahl');
  await page.goto('/index.php?option=com_fdshop&view=cart');
  await expect(page.locator('[data-cart-bundle]')).toHaveCount(0);
  diagnostics.expectClean();
});

test('registered customer can save, load and delete a composition', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await authenticateSiteUser(page);
  const dialog = await openBuilder(page);
  await dialog.locator('[data-bundle-pool-product="900100"] [data-bundle-add]').click();
  await dialog.locator('[data-bundle-pool-product="900105"] [data-bundle-add]').click();
  await expect(dialog.getByRole('button', { name: 'Bundle speichern' })).toBeEnabled();
  await expect(dialog.getByRole('button', { name: 'Bundle speichern' })).toHaveCSS('background-color', 'rgb(255, 255, 255)');
  page.once('dialog', prompt => prompt.accept('Mein E2E Bundle'));
  await dialog.getByRole('button', { name: 'Bundle speichern' }).click();
  await expect(dialog.locator('[data-bundle-message]')).toContainText('gespeichert');
  await dialog.getByRole('button', { name: 'Bundle-Konfigurator schließen' }).click();
  await page.getByRole('button', { name: 'Bundle zusammenstellen' }).click();
  await expect(dialog.locator('.fdshop-bundle__saved')).toContainText('Mein E2E Bundle');
  await dialog.locator('.fdshop-bundle__saved').getByRole('button', { name: 'Laden' }).click();
  await expect(dialog.locator('[data-bundle-quantity]')).toHaveCount(2);
  await dialog.locator('.fdshop-bundle__saved').getByRole('button', { name: 'Löschen' }).click();
  await expect(dialog.locator('.fdshop-bundle__saved')).toHaveCount(0);
  diagnostics.expectClean();
});

test('mobile fallback stays usable and reduced motion suppresses bundle animation', async ({ page, baseURL }) => {
  const diagnostics = await installDiagnostics(page, baseURL);
  await page.setViewportSize({ width: 390, height: 844 });
  await page.emulateMedia({ reducedMotion: 'reduce' });
  const dialog = await openBuilder(page);
  await expect(dialog.locator('.fdshop-bundle__workspace')).toBeVisible();
  await dialog.locator('[data-bundle-pool-product="900100"] [data-bundle-add]').click();
  await expect(dialog.locator('[data-bundle-chosen-product="900100"]')).toBeVisible();
  await dialog.locator('[data-bundle-pool-product="900105"] [data-bundle-add]').click();
  await expect(dialog.locator('[data-bundle-celebration]')).toBeVisible();
  await expect(dialog.locator('[data-bundle-celebration-effects]')).toHaveCSS('display', 'none');
  expect(await page.evaluate(() => matchMedia('(prefers-reduced-motion: reduce)').matches)).toBe(true);
  await expect(dialog.locator('.fdshop-bundle__chosen-card').last()).toHaveCSS('animation-name', 'none');
  await expect(dialog.locator('[data-bundle-progress-status]')).toContainText('5 % Rabatt aktiv', { timeout: 2400 });
  diagnostics.expectClean();
});
