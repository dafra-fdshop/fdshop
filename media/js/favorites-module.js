(() => {
  'use strict';

  const modules = () => Array.from(document.querySelectorAll('[data-fdshop-favorites-module]'));
  let requestId = 0;
  let controller = null;

  const paint = count => modules().forEach(root => {
    const value = Math.max(0, Number.parseInt(count, 10) || 0);
    root.querySelector('[data-favorites-module-count]').textContent = String(value);
    const link = root.querySelector('.fdshop-favorites-module__trigger');
    if (!link.getAttribute('aria-label').includes('Anmeldung erforderlich')) {
      link.setAttribute('aria-label', `Meine Favoriten öffnen, ${value} gespeichert`);
    }
  });

  const refresh = async () => {
    const root = modules()[0];
    if (!root) return;
    const current = ++requestId;
    controller?.abort();
    controller = new AbortController();
    try {
      const response = await fetch(root.dataset.counterUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' }, signal: controller.signal });
      const payload = await response.json();
      if (!response.ok || payload.success === false) throw new Error(payload.message || 'Der Favoritenzähler konnte nicht aktualisiert werden.');
      if (current === requestId) paint((payload.data || payload).count);
    } catch (error) {
      if (error.name !== 'AbortError') document.dispatchEvent(new CustomEvent('fdshop:favorites-counter-error', { detail: error }));
    }
  };

  document.addEventListener('fdshop:favorites-updated', event => {
    const count = event.detail?.defaultCount;
    if (Number.isInteger(Number(count))) paint(count); else refresh();
  });
})();
