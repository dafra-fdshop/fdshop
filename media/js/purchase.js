(() => {
  'use strict';

  const modal = document.querySelector('[data-purchase-modal]');
  document.addEventListener('click', event => {
    if (event.target.closest('[data-f3-info-open]')) document.querySelector('[data-f3-info-modal]')?.showModal();
    if (event.target.closest('[data-f3-info-close]')) event.target.closest('[data-f3-info-modal]')?.close();
  });
  let watchProductId = 0;
  document.addEventListener('click', event => {
    const button = event.target.closest('[data-watch-open]');
    if (!button) return;
    const watchModal = document.querySelector('[data-watch-dialog]');
    if (!watchModal) return;
    watchProductId = Number(button.dataset.watchProductId || 0);
    const product = watchModal.querySelector('[data-watch-product]');
    if (product) product.textContent = button.dataset.watchProductName || '';
    const message = watchModal.querySelector('[data-watch-message]');
    if (message) message.textContent = '';
    watchModal.showModal();
  });
  document.addEventListener('click', event => {
    if (event.target.closest('[data-watch-close]')) event.target.closest('[data-watch-dialog]')?.close();
  });
  document.addEventListener('click', async event => {
    const button = event.target.closest('[data-watch-activate]');
    if (!button) return;
    const watchModal = button.closest('[data-watch-dialog]');
    if (!watchModal) return;
    const tokenInput = watchModal.querySelector('[data-watch-token] input');
    const message = watchModal.querySelector('[data-watch-message]');
    if (!tokenInput || !watchProductId) return;
    button.disabled = true;
    const body = new FormData(); body.append(tokenInput.name, '1'); body.append('product_id', String(watchProductId));
    try {
      const response = await fetch(watchModal.dataset.watchUrl, {method:'POST', body, headers:{'X-Requested-With':'XMLHttpRequest'}});
      const payload = await response.json();
      if (!response.ok || payload.success === false) throw new Error(payload.message || 'Die Benachrichtigung konnte nicht aktiviert werden.');
      message.textContent = payload.message || 'Benachrichtigung wurde aktiviert.';
    } catch (reason) { message.textContent = reason.message || 'Die Benachrichtigung konnte nicht aktiviert werden.'; }
    finally { button.disabled = false; }
  });
  document.addEventListener('click', event => {
    if (event.target.closest('[data-product-question-open]')) document.querySelector('[data-product-question-dialog]')?.showModal();
    if (event.target.closest('[data-product-question-close]')) event.target.closest('[data-product-question-dialog]')?.close();
  });
  const token = document.querySelector('[data-purchase-token] input');
  const touchCapable = navigator.maxTouchPoints > 0;
  let returnFocus = null;

  if (!token) return;

  const formatQuantity = value => Number(value).toLocaleString('de-DE', { maximumFractionDigits: 3 });
  const closeModal = () => {
    if (modal?.open) modal.close();
    returnFocus?.focus();
  };

  modal?.querySelectorAll('[data-purchase-close]').forEach(button => button.addEventListener('click', closeModal));
  modal?.addEventListener('click', event => {
    if (event.target === modal) closeModal();
  });

  const showResult = (purchase, trigger) => {
    if (!modal) return;
    returnFocus = trigger;
    modal.querySelector('[data-purchase-title]').textContent = purchase.adjusted ? 'Menge angepasst' : 'Zum Warenkorb hinzugefügt';
    modal.querySelector('[data-purchase-message]').textContent = purchase.message;
    modal.querySelector('[data-purchase-product]').textContent = purchase.productName;
    modal.querySelector('[data-purchase-effective]').textContent = formatQuantity(purchase.effectiveQuantity);
    modal.querySelector('[data-purchase-price]').textContent = purchase.unitPrice;
    modal.querySelector('[data-purchase-amount]').textContent = purchase.lineAmount;
    modal.querySelector('[data-purchase-cart]').href = purchase.cartUrl;
    modal.showModal();
    modal.querySelector('[data-purchase-close]').focus();
  };

  if (!touchCapable) {
    document.addEventListener('mouseover', event => {
      const action = event.target.closest('[data-fdshop-purchase]');
      if (action && !action.contains(event.relatedTarget)) action.classList.add('is-open');
    });
    document.addEventListener('mouseout', event => {
      const commerce = event.target.closest('.fdshop-card__commerce');
      if (!commerce || commerce.contains(event.relatedTarget)) return;
      const action = commerce.querySelector('[data-fdshop-purchase]');
      if (action && !action.contains(document.activeElement)) action.classList.remove('is-open');
    });
  }

  document.addEventListener('click', async event => {
    const button = event.target.closest('[data-purchase-submit]');
    if (!button) return;
    const action = button.closest('[data-fdshop-purchase]');
    const quantity = action?.querySelector('[data-purchase-quantity]');
    const error = action?.querySelector('[data-purchase-error]');
    if (!action || !quantity || !error) return;
    if (touchCapable && !action.classList.contains('is-open')) {
      event.preventDefault();
      action.classList.add('is-open');
      quantity.focus();
      return;
    }

    error.hidden = true;
    error.textContent = '';
    button.disabled = true;
    quantity.disabled = true;
    const body = new FormData();
    body.append(token.name, '1');
    body.append('product_id', action.dataset.purchaseProductId);
    body.append('quantity', quantity.value);
    body.append('unit_variant', action.dataset.unitVariant || 'piece');

    try {
      const response = await fetch('index.php?option=com_fdshop&format=json&task=cart.add', {
        method: 'POST', body, headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const payload = await response.json();
      if (!response.ok || payload.success === false || !payload.data?.purchase) {
        throw new Error(payload.message || 'Das Produkt konnte nicht hinzugefügt werden.');
      }
      showResult(payload.data.purchase, button);
    } catch (reason) {
      error.textContent = reason.message || 'Das Produkt konnte nicht hinzugefügt werden.';
      error.hidden = false;
    } finally {
      button.disabled = false;
      quantity.disabled = false;
    }
  });
})();
