(() => {
  'use strict';
  const metaRoot = document.querySelector('[data-fdshop-meta-generator]');
  if (metaRoot) {
    const fields = {
      type: document.querySelector('#jform_meta_product_type'),
      title: document.querySelector('#jform_meta_title'),
      description: document.querySelector('#jform_meta_description'),
      name: document.querySelector('#jform_product_name'),
      manufacturer: document.querySelector('#jform_manufacturer_id'),
      shortDescription: document.querySelector('#jform_short_description'),
      shots: document.querySelector('#jform_shot_count'),
      caliber: document.querySelector('#jform_caliber'),
      nem: document.querySelector('#jform_nem'),
      duration: document.querySelector('#jform_burn_time'),
    };
    const cleanText = value => {
      const element = document.createElement('div');
      element.innerHTML = String(value || '');
      return (element.textContent || '').replace(/\s+/g, ' ').trim();
    };
    const measurement = (value, unit) => {
      const cleaned = cleanText(value);
      if (!cleaned || Number(cleaned.replace(',', '.')) === 0) return '';
      return new RegExp(`(?:^|\\s)${unit.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}$`, 'i').test(cleaned)
        ? cleaned
        : `${cleaned} ${unit}`;
    };
    const number = value => {
      const parsed = Number(String(value || '').replace(',', '.'));
      if (!Number.isFinite(parsed) || parsed <= 0) return '';
      return parsed.toLocaleString('de-DE', { maximumFractionDigits: 3 });
    };
    const manufacturerName = () => {
      if (!fields.manufacturer || fields.manufacturer.value === '0' || fields.manufacturer.value === '') return '';
      return cleanText(fields.manufacturer.selectedOptions[0]?.textContent);
    };
    const suggestion = () => {
      const name = cleanText(fields.name?.value);
      const manufacturer = manufacturerName();
      const type = cleanText(fields.type?.selectedOptions[0]?.textContent);
      const intro = cleanText(fields.shortDescription?.value);
      const facts = [
        number(fields.shots?.value) ? `${number(fields.shots.value)} Schuss` : '',
        measurement(fields.caliber?.value, 'mm'),
        number(fields.nem?.value) ? `${number(fields.nem.value)} g NEM` : '',
        measurement(fields.duration?.value, 's'),
      ].filter(Boolean);
      const title = `${manufacturer ? `${manufacturer} ` : ''}${name} | ${type}`;
      let description = manufacturer ? `${name} von ${manufacturer}` : name;
      if (intro) description += `: ${intro.replace(/[.!?]+$/, '')}.`;
      else if (facts.length) description += ':';
      if (facts.length) description += `${intro ? ' ' : ' '}${facts.join(' · ')}.`;
      return { title, description: description.trim() };
    };
    const updateCounter = (field, kind, limit) => {
      const output = metaRoot.querySelector(`[data-fdshop-meta-counter="${kind}"]`);
      if (!field || !output) return;
      const count = Array.from(field.value).length;
      output.textContent = `${count} ${metaRoot.dataset.counterLabel}${count > limit ? ` – ${metaRoot.dataset.counterWarning}` : ''}`;
      output.classList.toggle('text-danger', count > limit);
      output.classList.toggle('text-muted', count <= limit);
    };
    const updateCounters = () => {
      updateCounter(fields.title, 'title', 60);
      updateCounter(fields.description, 'description', 160);
    };
    fields.title?.addEventListener('input', updateCounters);
    fields.description?.addEventListener('input', updateCounters);
    metaRoot.querySelector('[data-fdshop-meta-fill]')?.addEventListener('click', () => {
      if (!fields.type?.value) {
        window.alert(metaRoot.dataset.messageType);
        return;
      }
      if (!cleanText(fields.name?.value)) {
        window.alert(metaRoot.dataset.messageName);
        return;
      }
      const generated = suggestion();
      if ((fields.title.value || fields.description.value) && !window.confirm(metaRoot.dataset.messageConfirm)) return;
      fields.title.value = generated.title;
      fields.description.value = generated.description;
      fields.title.dispatchEvent(new Event('input', { bubbles: true }));
      fields.description.dispatchEvent(new Event('input', { bubbles: true }));
    });
    updateCounters();
  }

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
