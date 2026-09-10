(() => {
  'use strict';

  const actions = document.querySelectorAll('[data-fdshop-purchase]');
  const modal = document.querySelector('[data-purchase-modal]');
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

    button.addEventListener('click', async event => {
      if (touchCapable && action.classList.contains('fdshop-purchase--card') && !action.classList.contains('is-open')) {
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
