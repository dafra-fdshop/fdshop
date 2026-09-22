(() => {
  'use strict';
  document.querySelectorAll('[data-fdshop-media-action]').forEach(button => {
    button.addEventListener('click', () => {
      const item = button.closest('[data-fdshop-media-item]');
      const action = button.dataset.fdshopMediaAction;
      if (!item || !action) return;
      if (action === 'delete' && !window.confirm('Dieses Produktbild wirklich löschen?')) return;
      document.querySelector('input[name="media_id"]').value = item.dataset.fdshopMediaItem;
      document.querySelector('input[name="media_ordering"]').value = item.querySelector('[data-fdshop-media-ordering]')?.value || '0';
      const tasks = {primary: 'product.setPrimaryImage', ordering: 'product.updateImageOrdering', delete: 'product.deleteImage'};
      Joomla.submitbutton(tasks[action]);
    });
  });
  const type = document.querySelector('#jform_unit_type');
  const quantity = document.querySelector('#jform_unit_quantity');
  const discountType = document.querySelector('#jform_unit_discount_type');
  const discountValue = document.querySelector('#jform_unit_discount_value');
  const sale = document.querySelector('#jform_sale_price');
  const reduced = document.querySelector('#jform_discount_price');
  const reducedActive = document.querySelector('#jform_discount_active');
  const preview = document.querySelector('[data-fdshop-package-preview]');
  if (!type || !quantity || !discountType || !discountValue || !preview) return;
  const update = () => {
    const piece = reducedActive?.value === '1' && Number(reduced?.value) > 0 ? Number(reduced.value) : Number(sale?.value || 0);
    const isPiece = type.value === 'Stück';
    quantity.disabled = isPiece;
    discountType.disabled = isPiece;
    discountValue.disabled = isPiece || discountType.value === 'none';
    if (isPiece) { quantity.value = '1'; discountType.value = 'none'; discountValue.value = '0'; preview.textContent = 'Stückverkauf – keine Verpackungseinheit'; return; }
    const base = piece * Number(quantity.value || 0);
    const price = discountType.value === 'percent' ? base * (1 - Number(discountValue.value || 0) / 100) : discountType.value === 'amount' ? Number(discountValue.value || 0) : base;
    preview.textContent = `Verpackungspreis: ${price.toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2})} € · Ersparnis: ${Math.max(0, base - price).toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2})} €`;
  };
  [type, quantity, discountType, discountValue, sale, reduced, reducedActive].forEach(field => field?.addEventListener('input', update));
  update();
})();
