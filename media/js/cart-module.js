(() => {
  'use strict';

  const modules = () => Array.from(document.querySelectorAll('[data-fdshop-cart-module]'));
  let requestId = 0;
  let controller = null;

  const position = item => {
    const row = document.createElement('li');
    row.className = 'fdshop-cart-module__item';
    row.dataset.cartModulePosition = item.type;
    const image = document.createElement('img');
    image.src = item.image;
    image.alt = '';
    image.width = 64;
    image.height = 64;
    image.loading = 'lazy';
    const copy = document.createElement('div');
    const name = item.url ? document.createElement('a') : document.createElement('span');
    if (item.url) name.href = item.url;
    name.textContent = item.name;
    const title = document.createElement('strong');
    const quantity = document.createElement('span');
    quantity.textContent = `${item.quantity} × `;
    title.append(quantity, name);
    copy.append(title);
    const total = document.createElement('b');
    total.textContent = item.total;
    row.append(image, copy, total);
    return row;
  };

  const paint = summary => modules().forEach(root => {
    root.querySelector('[data-cart-module-count]').textContent = summary.countFormatted;
    root.querySelector('[data-cart-module-footer-count]').textContent = summary.countFormatted;
    root.querySelector('[data-cart-module-total]').textContent = summary.subtotal;
    root.querySelector('[data-cart-module-empty]').hidden = !summary.empty;
    root.querySelector('[data-cart-module-items]').replaceChildren(...summary.positions.map(position));
    const trigger = root.querySelector('[data-cart-module-open]');
    trigger.setAttribute('aria-label', `Warenkorb öffnen, ${summary.countFormatted} Artikel`);
  });

  const refresh = async () => {
    const root = modules()[0];
    if (!root) return;
    const current = ++requestId;
    controller?.abort();
    controller = new AbortController();
    try {
      const response = await fetch(root.dataset.summaryUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' }, signal: controller.signal });
      const payload = await response.json();
      if (!response.ok || payload.success === false) throw new Error(payload.message || 'Der Warenkorb konnte nicht aktualisiert werden.');
      if (current === requestId) paint(payload.data);
    } catch (error) {
      if (error.name !== 'AbortError') document.dispatchEvent(new CustomEvent('fdshop:cart-summary-error', { detail: error }));
    }
  };

  const focusable = panel => Array.from(panel.querySelectorAll('a[href],button:not([disabled]),[tabindex]:not([tabindex="-1"])')).filter(element => !element.hidden);
  const close = root => {
    const panel = root.querySelector('[data-cart-module-panel]');
    const backdrop = root.querySelector('[data-cart-module-backdrop]');
    const trigger = root.querySelector('[data-cart-module-open]');
    if (!panel.classList.contains('is-open')) return;
    panel.classList.remove('is-open');
    backdrop.classList.remove('is-open');
    trigger.setAttribute('aria-expanded', 'false');
    document.documentElement.classList.remove('fdshop-cart-offcanvas-open');
    const finish = () => {
      root._fdshopCartCloseTimer = null;
      if (!panel.classList.contains('is-open')) { panel.hidden = true; backdrop.hidden = true; }
    };
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) finish(); else root._fdshopCartCloseTimer = window.setTimeout(finish, 220);
    trigger.focus();
  };
  const open = root => {
    modules().forEach(other => { if (other !== root) close(other); });
    const panel = root.querySelector('[data-cart-module-panel]');
    const backdrop = root.querySelector('[data-cart-module-backdrop]');
    if (root._fdshopCartCloseTimer) window.clearTimeout(root._fdshopCartCloseTimer);
    root._fdshopCartCloseTimer = null;
    panel.hidden = false;
    backdrop.hidden = false;
    requestAnimationFrame(() => { panel.classList.add('is-open'); backdrop.classList.add('is-open'); });
    root.querySelector('[data-cart-module-open]').setAttribute('aria-expanded', 'true');
    document.documentElement.classList.add('fdshop-cart-offcanvas-open');
    panel.focus();
  };

  document.addEventListener('click', event => {
    const trigger = event.target.closest('[data-cart-module-open]');
    if (trigger) { open(trigger.closest('[data-fdshop-cart-module]')); return; }
    const target = event.target.closest('[data-cart-module-close],[data-cart-module-backdrop]');
    if (target) close(target.closest('[data-fdshop-cart-module]'));
  });
  document.addEventListener('keydown', event => {
    const root = document.querySelector('[data-fdshop-cart-module] [data-cart-module-panel].is-open')?.closest('[data-fdshop-cart-module]');
    if (!root) return;
    if (event.key === 'Escape') { event.preventDefault(); close(root); return; }
    if (event.key !== 'Tab') return;
    const elements = focusable(root.querySelector('[data-cart-module-panel]'));
    if (!elements.length) return;
    if (event.shiftKey && document.activeElement === elements[0]) { event.preventDefault(); elements.at(-1).focus(); }
    else if (!event.shiftKey && document.activeElement === elements.at(-1)) { event.preventDefault(); elements[0].focus(); }
  });
  document.addEventListener('fdshop:cart-updated', refresh);
})();
