document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('[data-fdshop-order-draft]');
  if (!form) return;
  form.querySelectorAll('[data-remove-existing]').forEach(button => button.addEventListener('click', () => {
    const row = button.closest('[data-order-item-row]');
    const removed = row?.querySelector('[data-removed]');
    if (!row || !removed) return;
    const isRemoved = removed.value === '1';
    removed.value = isRemoved ? '0' : '1';
    row.classList.toggle('table-danger', !isRemoved);
    row.querySelector('input[type="number"]').readOnly = !isRemoved;
    button.textContent = isRemoved ? 'Entfernen' : 'Wiederherstellen';
  }));
  let index = 0;
  const list = form.querySelector('[data-new-items]');
  form.querySelector('[data-add-draft]')?.addEventListener('click', () => {
    const select = form.querySelector('#jform_product_id');
    const quantity = form.querySelector('#jform_quantity');
    const option = select?.selectedOptions?.[0];
    const value = Number(quantity?.value || 0);
    if (!option?.value || !Number.isInteger(value) || value < 1) return;
    const row = document.createElement('div');
    row.className = 'alert alert-secondary d-flex justify-content-between align-items-center py-2';
    row.innerHTML = `<span></span><span><input type="hidden" name="new_items[${index}][product_id]"><input type="hidden" name="new_items[${index}][quantity]"><button type="button" class="btn btn-sm btn-outline-danger">Entfernen</button></span>`;
    row.querySelector('span').textContent = `${option.dataset.name} (${option.dataset.sku}) × ${value}`;
    row.querySelector('[name$="[product_id]"]').value = option.value;
    row.querySelector('[name$="[quantity]"]').value = String(value);
    row.querySelector('button').addEventListener('click', () => row.remove());
    list.append(row); index += 1; select.value = ''; quantity.value = '1';
  });
});
