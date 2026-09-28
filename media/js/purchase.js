(() => {
  'use strict';

  const actions = document.querySelectorAll('[data-fdshop-purchase]');
  const modal = document.querySelector('[data-purchase-modal]');
  const f3Modal = document.querySelector('[data-f3-info-modal]');
  document.querySelectorAll('[data-f3-info-open]').forEach(button => button.addEventListener('click', () => f3Modal?.showModal()));
  f3Modal?.querySelectorAll('[data-f3-info-close]').forEach(button => button.addEventListener('click', () => f3Modal.close()));
  const watchModal = document.querySelector('[data-watch-dialog]');
  let watchProductId = 0;
  document.querySelectorAll('[data-watch-open]').forEach(button => button.addEventListener('click', () => {
    if (!watchModal) return;
    watchProductId = Number(button.dataset.watchProductId || 0);
    const product = watchModal.querySelector('[data-watch-product]');
    if (product) product.textContent = button.dataset.watchProductName || '';
    const message = watchModal.querySelector('[data-watch-message]');
    if (message) message.textContent = '';
    watchModal.showModal();
  }));
  watchModal?.querySelectorAll('[data-watch-close]').forEach(button => button.addEventListener('click', () => watchModal.close()));
  watchModal?.querySelector('[data-watch-activate]')?.addEventListener('click', async event => {
    const button = event.currentTarget;
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
  const questionDialog = document.querySelector('[data-product-question-dialog]');
  document.querySelectorAll('[data-product-question-open]').forEach(button => button.addEventListener('click', () => questionDialog?.showModal()));
  questionDialog?.querySelectorAll('[data-product-question-close]').forEach(button => button.addEventListener('click', () => questionDialog.close()));
  const token = document.querySelector('[data-purchase-token] input');
  const touchCapable = navigator.maxTouchPoints > 0;
  let returnFocus = null;

  if (!actions.length || !token) return;

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

  actions.forEach(action => {
    const button = action.querySelector('[data-purchase-submit]');
    const quantity = action.querySelector('[data-purchase-quantity]');
    const error = action.querySelector('[data-purchase-error]');

    if (!touchCapable) {
      action.addEventListener('mouseenter', () => action.classList.add('is-open'));
      action.closest('.fdshop-card__commerce')?.addEventListener('mouseleave', () => {
        if (!action.contains(document.activeElement)) action.classList.remove('is-open');
      });
    }

    button.addEventListener('click', async event => {
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
  });
})();
