(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var cart = document.querySelector('[data-fdshop-cart]');
        var token = cart && cart.querySelector('[data-cart-token] input');
        if (!cart || !token) return;

        var message = cart.querySelector('[data-fdshop-cart-message]');
        var endpoint = 'index.php?option=com_fdshop&format=json&task=cart.';

        function notify(text, error) {
            message.textContent = text;
            message.className = 'fdshop-cart__message alert ' + (error ? 'alert-danger' : 'alert-success');
            message.hidden = false;
        }

        async function request(task, values) {
            var body = new FormData();
            body.append(token.name, '1');
            Object.keys(values || {}).forEach(function (key) { body.append(key, values[key]); });
            var controls = Array.from(cart.querySelectorAll('button:not([disabled]), input:not([disabled]), textarea:not([disabled])'));
            controls.forEach(function (control) { control.disabled = true; });
            try {
                var response = await fetch(endpoint + task, { method: 'POST', body: body, credentials: 'same-origin', headers: { Accept: 'application/json' } });
                var payload = await response.json();
                if (!response.ok || payload.success === false) throw new Error(payload.message || 'Der Warenkorb konnte nicht aktualisiert werden.');
                update(payload.data);
                if (payload.message) notify(payload.message, false);
                return payload.data;
            } finally {
                controls.forEach(function (control) { control.disabled = false; });
            }
        }

        function update(state) {
            state.items.forEach(function (item) {
                var row = cart.querySelector('[data-cart-item="' + item.id + '"]');
                if (!row) return;
                row.querySelector('[data-cart-quantity]').value = String(item.quantity);
                row.dataset.confirmedQuantity = String(item.quantity);
                row.querySelector('[data-cart-unit-price]').textContent = item.unitPrice;
                row.querySelector('[data-cart-line-value]').textContent = item.lineTotal;
            });
            cart.querySelectorAll('[data-cart-item]').forEach(function (row) {
                if (!state.items.some(function (item) { return String(item.id) === row.dataset.cartItem; })) row.remove();
            });
            var empty = state.items.length === 0;
            cart.querySelector('[data-fdshop-cart-empty]').hidden = !empty;
            cart.querySelector('[data-fdshop-cart-items]').hidden = empty;
            cart.querySelector('[data-cart-subtotal]').textContent = state.subtotal;
            cart.querySelector('[data-cart-summary-subtotal]').textContent = state.subtotal;
            cart.querySelector('[data-cart-shipment-fee]').textContent = state.shipmentFee;
            cart.querySelector('[data-cart-shipment-selection-fee]').textContent = state.shipmentFee;
            cart.querySelector('[data-cart-payment-fee]').textContent = state.paymentFee;
            cart.querySelector('[data-cart-payment-selection-fee]').textContent = state.paymentFee;
            cart.querySelector('[data-cart-total]').textContent = state.total;
            cart.querySelector('[data-cart-shipment-name]').textContent = state.shipmentName;
            cart.querySelector('[data-cart-payment-name]').textContent = state.paymentName;
        }

        function quantity(row, delta) {
            var field = row.querySelector('[data-cart-quantity]');
            field.value = String(Number(field.value) + (Number(field.step) * delta));
        }

        cart.addEventListener('click', function (event) {
            var button = event.target.closest('button');
            if (!button) return;
            var row = button.closest('[data-cart-item]');
            if (button.matches('[data-cart-decrease]')) return quantity(row, -1);
            if (button.matches('[data-cart-increase]')) return quantity(row, 1);
            if (button.matches('[data-cart-update]')) request('updateQuantity', { cart_id: row.dataset.cartItem, quantity: row.querySelector('[data-cart-quantity]').value }).then(function () { notify('Menge wurde aktualisiert.', false); }).catch(function (error) { row.querySelector('[data-cart-quantity]').value = row.dataset.confirmedQuantity; notify(error.message, true); });
            if (button.matches('[data-cart-remove]')) request('remove', { cart_id: row.dataset.cartItem }).then(function () { notify('Produkt wurde entfernt.', false); }).catch(function (error) { notify(error.message, true); });
            if (button.matches('[data-cart-open]')) cart.querySelector('[data-cart-dialog="' + button.dataset.cartOpen + '"]').showModal();
            if (button.matches('[data-cart-select-shipment]')) request('selectShipment', { shipment_id: button.dataset.cartSelectShipment }).then(function () { button.closest('dialog').close(); notify('Abholstation wurde geändert.', false); }).catch(function (error) { notify(error.message, true); });
            if (button.matches('[data-cart-select-payment]')) request('selectPayment', { payment_id: button.dataset.cartSelectPayment }).then(function () { button.closest('dialog').close(); notify('Zahlungsart wurde geändert.', false); }).catch(function (error) { notify(error.message, true); });
            if (button.matches('[data-cart-order]')) request('orderUnavailable', {}).then(function () { notify('Die Bestellfunktion wird in einem folgenden Paket aktiviert.', false); }).catch(function (error) { notify(error.message, true); });
        });
    });
}());
