(function () {
    'use strict';

    var dialog = document.querySelector('[data-fdshop-bundle-dialog]');
    if (!dialog) return;
    var content = dialog.querySelector('[data-fdshop-bundle-content]');
    var token = document.querySelector('[data-fdshop-bundle-token] input');
    var currentId = 0;
    var currentData = null;
    var currentSavedId = 0;
    var timer = null;

    function request(url, data, method) {
        var options = {credentials: 'same-origin', headers: {'Accept': 'application/json'}};
        if (method === 'POST') {
            var body = new URLSearchParams(data || {});
            if (token) body.set(token.name, '1');
            options.method = 'POST'; options.body = body;
        }
        return fetch(url, options).then(function (r) { return r.json(); }).then(function (r) {
            if (r.success === false) throw new Error(r.message || 'Die Bundle-Aktion ist fehlgeschlagen.');
            return r.data || r;
        });
    }

    function money(value, currency) { return Number(value || 0).toLocaleString('de-DE', {style: 'currency', currency: currency || 'EUR'}); }
    function selection() { var result = {}; content.querySelectorAll('[data-bundle-quantity]').forEach(function (input) { var q = Number(input.value || 0); if (q > 0) result[input.dataset.productId] = q; }); return result; }
    function message(text, error) { var node = content.querySelector('[data-bundle-message]'); if (node) { node.textContent = text || ''; node.classList.toggle('is-error', !!error); } }
    function calculate() {
        renderChosen();
        window.clearTimeout(timer);
        timer = window.setTimeout(function () {
            request(dialog.dataset.calculateUrl, {bundle_id: currentId, items: JSON.stringify(selection())}, 'POST').then(function (data) {
                var summary = content.querySelector('[data-bundle-summary]');
                summary.querySelector('[data-bundle-distinct]').textContent = data.distinct_product_count;
                summary.querySelector('[data-bundle-count]').textContent = data.total_quantity;
                summary.querySelector('[data-bundle-subtotal]').textContent = money(data.subtotal_gross, data.currency);
                summary.querySelector('[data-bundle-discount]').textContent = '-' + money(data.discount_amount_gross, data.currency);
                summary.querySelector('[data-bundle-total]').textContent = money(data.total_gross, data.currency);
                message(''); content.querySelector('[data-bundle-cart]').disabled = false;
            }).catch(function (e) { message(e.message, true); content.querySelector('[data-bundle-cart]').disabled = true; });
        }, 120);
    }

    function renderChosen() {
        var target = content.querySelector('[data-bundle-chosen-list]');
        if (!target) return;
        target.replaceChildren();
        content.querySelectorAll('[data-bundle-quantity]').forEach(function (input) {
            var quantity = Number(input.value || 0); if (quantity < 1) return;
            var card = input.closest('.fdshop-bundle__product'); var row = document.createElement('div');
            row.className = 'fdshop-bundle__chosen-item'; row.textContent = quantity + ' × ' + card.querySelector('strong').textContent; target.appendChild(row);
        });
        if (!target.children.length) target.textContent = 'Noch keine Produkte gewählt.';
    }

    function render(data) {
        currentData = data; content.replaceChildren();
        var b = data.bundle; var selected = {};
        (data.selection || []).forEach(function (row) { selected[row.product_id] = row.quantity; });
        var header = document.createElement('header'); header.innerHTML = '<h2></h2><p></p>';
        header.querySelector('h2').textContent = b.bundle_name; header.querySelector('p').textContent = b.description || '';
        var info = document.createElement('div'); info.className = 'fdshop-bundle__info'; info.textContent = 'Wählen Sie mindestens zwei verschiedene Produkte. Maximal ' + b.max_quantity_per_product + ' Stück je Produkt.';
        var rules = document.createElement('p'); rules.className = 'fdshop-bundle__rules'; rules.textContent = (data.rules || []).map(function (r) { return 'ab ' + Number(r.min_quantity) + ' Stk. −' + Number(r.discount_percent).toLocaleString('de-DE') + ' %'; }).join(' · ');
        if (data.is_authenticated && data.saved && data.saved.length) {
            var savedBox = document.createElement('section'); savedBox.className = 'fdshop-bundle__saved'; savedBox.innerHTML = '<h3>Gespeicherte Zusammenstellungen</h3>';
            data.saved.forEach(function (saved) { var row=document.createElement('div'); var label=document.createElement('span'); label.textContent=saved.saved_name; var load=document.createElement('button'); load.type='button'; load.className='btn btn-sm btn-outline-primary'; load.textContent='Laden'; load.addEventListener('click',function(){ currentSavedId=Number(saved.id); request(dialog.dataset.builderUrl+'&bundle_id='+currentId+'&saved_bundle_id='+saved.id,null,'GET').then(render); }); var remove=document.createElement('button'); remove.type='button'; remove.className='btn btn-sm btn-outline-danger'; remove.textContent='Löschen'; remove.addEventListener('click',function(){ request(dialog.dataset.deleteUrl,{saved_bundle_id:saved.id},'POST').then(function(){open(currentId);}); }); row.append(label,load,remove); savedBox.appendChild(row); }); content.appendChild(savedBox);
        }
        var grid = document.createElement('div'); grid.className = 'fdshop-bundle__grid';
        var pool = document.createElement('section'); pool.className = 'fdshop-bundle__pool'; pool.innerHTML = '<h3>Produkte</h3>';
        (data.products || []).forEach(function (p) {
            var card = document.createElement('article'); card.className = 'fdshop-bundle__product';
            var img = document.createElement('img'); img.src = p.image_path ? '/' + String(p.image_path).replace(/^\/+/, '') : '/media/com_fdshop/images/product-placeholder.svg'; img.alt = '';
            var copy = document.createElement('div'); var name = document.createElement('strong'); name.textContent = p.product_name; var sku = document.createElement('small'); sku.textContent = p.sku; var price = document.createElement('span'); price.textContent = money(p.effective_price, p.currency);
            copy.append(name, sku, price); var controls = document.createElement('div'); controls.className = 'fdshop-bundle__controls';
            var minus = document.createElement('button'); minus.type = 'button'; minus.textContent = '−'; minus.setAttribute('aria-label', 'Menge reduzieren');
            var input = document.createElement('input'); input.type = 'number'; input.min = '0'; input.max = String(b.max_quantity_per_product); input.step = '1'; input.value = String(selected[p.id] || 0); input.dataset.bundleQuantity = ''; input.dataset.productId = p.id; input.setAttribute('aria-label', 'Anzahl ' + p.product_name);
            var plus = document.createElement('button'); plus.type = 'button'; plus.textContent = '+'; plus.setAttribute('aria-label', 'Menge erhöhen');
            minus.addEventListener('click', function () { input.value = Math.max(0, Number(input.value) - 1); calculate(); }); plus.addEventListener('click', function () { input.value = Math.min(Number(input.max), Number(input.value) + 1); calculate(); }); input.addEventListener('change', function () { input.value = Math.max(0, Math.min(Number(input.max), Math.round(Number(input.value) || 0))); calculate(); });
            controls.append(minus, input, plus); card.append(img, copy, controls); pool.appendChild(card);
        });
        var chosen = document.createElement('section'); chosen.className = 'fdshop-bundle__chosen'; chosen.innerHTML = '<h3>Meine Zusammenstellung</h3><div data-bundle-chosen-list></div>';
        grid.append(pool, chosen);
        var summary = document.createElement('section'); summary.className = 'fdshop-bundle__summary'; summary.dataset.bundleSummary = ''; summary.innerHTML = '<dl><div><dt>Verschiedene Produkte</dt><dd data-bundle-distinct>0</dd></div><div><dt>Gesamtmenge</dt><dd data-bundle-count>0</dd></div><div><dt>Summe Einzelpreise</dt><dd data-bundle-subtotal>–</dd></div><div><dt>Bundle-Rabatt</dt><dd data-bundle-discount>–</dd></div><div class="total"><dt>Bundle-Preis</dt><dd data-bundle-total>–</dd></div></dl>';
        var actions = document.createElement('div'); actions.className = 'fdshop-bundle__actions';
        var clear = document.createElement('button'); clear.type = 'button'; clear.className = 'btn btn-secondary'; clear.textContent = 'Alle löschen'; clear.addEventListener('click', function () { content.querySelectorAll('[data-bundle-quantity]').forEach(function (i) { i.value = 0; }); calculate(); });
        var save = document.createElement('button'); save.type = 'button'; save.className = 'btn btn-outline-primary'; save.textContent = 'Speichern'; save.disabled = !data.is_authenticated; save.title = save.disabled ? 'Bitte anmelden, um Bundles zu speichern.' : '';
        save.addEventListener('click', function () { var name = window.prompt('Name der gespeicherten Zusammenstellung', b.bundle_name); if (name === null) return; request(dialog.dataset.saveUrl, {bundle_id: currentId, saved_bundle_id: currentSavedId || '', saved_name: name, items: JSON.stringify(selection())}, 'POST').then(function (result) { currentSavedId=Number(result.saved_bundle_id || 0); message('Bundle wurde gespeichert.'); }).catch(function (e) { message(e.message, true); }); });
        var cart = document.createElement('button'); cart.type = 'button'; cart.className = 'btn btn-primary'; cart.dataset.bundleCart = ''; cart.textContent = 'In den Warenkorb'; cart.disabled = true; cart.addEventListener('click', function () { request(dialog.dataset.cartUrl, {bundle_id: currentId, cart_bundle_id: cart.dataset.cartBundleId || '', items: JSON.stringify(selection())}, 'POST').then(function () { dialog.close(); window.location.href = 'index.php?option=com_fdshop&view=cart'; }).catch(function (e) { message(e.message, true); }); });
        actions.append(clear, save, cart); var status = document.createElement('p'); status.dataset.bundleMessage = ''; status.setAttribute('role', 'status'); summary.append(actions, status); content.append(header, info, rules, grid, summary); renderChosen(); calculate();
    }

    function open(id) { currentId = Number(id); currentSavedId = 0; if (!currentId) return; content.textContent = 'Bundle wird geladen…'; if (!dialog.open) dialog.showModal(); request(dialog.dataset.builderUrl + '&bundle_id=' + encodeURIComponent(currentId), null, 'GET').then(render).catch(function (e) { content.textContent = e.message; }); }
    document.querySelectorAll('[data-fdshop-bundle-open]').forEach(function (button) { button.addEventListener('click', function () { open(button.dataset.fdshopBundleOpen); }); });
    var chooser = document.querySelector('[data-fdshop-bundle-choice]'); var chooserButton = document.querySelector('[data-fdshop-bundle-choice-open]'); if (chooser && chooserButton) chooserButton.addEventListener('click', function () { open(chooser.value); });
    var params = new URLSearchParams(window.location.search); if (params.get('bundle_id')) { currentId = Number(params.get('bundle_id')); content.textContent = 'Bundle wird geladen…'; dialog.showModal(); request(dialog.dataset.builderUrl + '&bundle_id=' + encodeURIComponent(currentId) + '&cart_bundle_id=' + encodeURIComponent(params.get('cart_bundle_id') || 0), null, 'GET').then(render).then(function () { var cartButton = content.querySelector('[data-bundle-cart]'); if (cartButton) cartButton.dataset.cartBundleId = params.get('cart_bundle_id') || ''; }).catch(function (e) { content.textContent = e.message; }); }
    dialog.querySelector('[data-fdshop-bundle-close]').addEventListener('click', function () { dialog.close(); }); dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });
}());
