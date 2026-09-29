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
                if (payload.data && Array.isArray(payload.data.items)) update(payload.data);
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
            var empty = state.items.length === 0 && (!state.bundles || state.bundles.length === 0);
            cart.querySelector('[data-fdshop-cart-empty]').hidden = !empty;
            cart.querySelector('[data-fdshop-cart-items]').hidden = state.items.length === 0;
            cart.querySelector('[data-cart-subtotal]').textContent = state.subtotal;
            cart.querySelector('[data-cart-summary-subtotal]').textContent = state.subtotal;
            cart.querySelector('[data-cart-shipment-fee]').textContent = state.shipmentFee;
            cart.querySelector('[data-cart-shipment-selection-fee]').textContent = state.shipmentFee;
            cart.querySelector('[data-cart-payment-fee]').textContent = state.paymentFee;
            cart.querySelector('[data-cart-payment-selection-fee]').textContent = state.paymentFee;
            cart.querySelector('[data-cart-coupon-discount]').textContent = state.couponDiscount;
            cart.querySelector('[data-cart-coupon-code]').value = state.couponCode;
            cart.querySelector('[data-cart-total]').textContent = state.total;
            cart.querySelector('[data-cart-shipment-name]').textContent = state.shipmentName;
            cart.querySelector('[data-cart-payment-name]').textContent = state.paymentName;
            cart.dataset.paypalEnabled = String(state.paymentPayPalEnabled || 0);
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
            if (button.matches('[data-cart-bundle-remove]')) {
                var bundle = button.closest('[data-cart-bundle]');
                var body = new FormData(); body.append(token.name, '1'); body.append('cart_bundle_id', bundle.dataset.cartBundle);
                fetch('index.php?option=com_fdshop&format=json&task=bundle.removeFromCart', {method: 'POST', body: body, credentials: 'same-origin', headers: {Accept: 'application/json'}}).then(function (response) { return response.json(); }).then(function (payload) { if (payload.success === false) throw new Error(payload.message); window.location.reload(); }).catch(function (error) { notify(error.message, true); });
            }
            if (button.matches('[data-cart-open]')) cart.querySelector('[data-cart-dialog="' + button.dataset.cartOpen + '"]').showModal();
            if (button.matches('[data-cart-select-shipment]')) request('selectShipment', { shipment_id: button.dataset.cartSelectShipment }).then(function () { button.closest('dialog').close(); notify('Abholstation wurde geändert.', false); }).catch(function (error) { notify(error.message, true); });
            if (button.matches('[data-cart-select-payment]')) request('selectPayment', { payment_id: button.dataset.cartSelectPayment }).then(function () { cart.dataset.paypalEnabled = button.dataset.paypalEnabled || '0'; setPayPalReady(cart.dataset.paypalEnabled!=='1'||Boolean(paypalSession)); if(cart.dataset.paypalEnabled==='1')loadPayPal().catch(function(error){notify(error.message,true);}); button.closest('dialog').close(); notify('Zahlungsart wurde geändert.', false); }).catch(function (error) { notify(error.message, true); });
            if (button.matches('[data-cart-apply-coupon]')) request('applyCoupon', { coupon_code: cart.querySelector('[data-cart-coupon-code]').value }).catch(function (error) { notify(error.message, true); });
            if (button.matches('[data-cart-order]')) {
                var terms = cart.querySelector('[data-cart-terms]');
                if (cart.dataset.paypalEnabled === '1') {
                    startPayPal({order_note: cart.querySelector('[data-cart-remark]').value, terms_accepted: terms && terms.checked ? '1' : '0', submission_id: cart.querySelector('[data-cart-submission]').value}).catch(function (error) { notify(error.message, true); });
                    return;
                }
                request('checkout', {order_note: cart.querySelector('[data-cart-remark]').value, terms_accepted: terms && terms.checked ? '1' : '0', submission_id: cart.querySelector('[data-cart-submission]').value})
                    .then(function (state) { window.location.assign(state.confirmation_url); })
                    .catch(function (error) { notify(error.message, true); });
            }
        });

        var paypalSession;
        var paypalStorageKey='fdshop.paypal.state';
        var paypalState=restorePayPalState();
        var paypalLoading;
        function restorePayPalState() {
            try {
                var state=JSON.parse(sessionStorage.getItem(paypalStorageKey)||'null');
                if(!state||!state.session_token||!state.order_id||!state.expires_at||new Date(state.expires_at).getTime()<=Date.now()){sessionStorage.removeItem(paypalStorageKey);return null;}
                return state;
            } catch(error){sessionStorage.removeItem(paypalStorageKey);return null;}
        }
        function storePayPalState(state){paypalState=state;sessionStorage.setItem(paypalStorageKey,JSON.stringify(state));}
        function clearPayPalState(){paypalState=null;sessionStorage.removeItem(paypalStorageKey);}
        // PayPal start() must remain in the direct click activation; never await SDK readiness there.
        function setPayPalReady(ready) {
            var button=cart.querySelector('[data-cart-order]');
            if(cart.dataset.paypalEnabled!=='1'){button.disabled=false;button.removeAttribute('aria-busy');return;}
            button.disabled=!ready;
            if(ready)button.removeAttribute('aria-busy');else button.setAttribute('aria-busy','true');
        }
        async function postPayment(task, values) {
            var body = new FormData(); body.append(token.name, '1');
            Object.keys(values || {}).forEach(function (key) { body.append(key, values[key]); });
            var response = await fetch('index.php?option=com_fdshop&format=json&task=payment.' + task, {method:'POST',body:body,credentials:'same-origin',headers:{Accept:'application/json'}});
            var payload = await response.json();
            if(!response.ok || payload.success===false) throw new Error(payload.message || 'PayPal konnte nicht verarbeitet werden.');
            return payload.data;
        }
        function countdown(expiresAt) {
            var panel=cart.querySelector('[data-paypal-progress]');var output=cart.querySelector('[data-paypal-countdown]');panel.hidden=false;
            clearInterval(cart._paypalTimer);var tick=function(){var seconds=Math.max(0,Math.floor((new Date(expiresAt).getTime()-Date.now())/1000));output.textContent=String(Math.floor(seconds/60)).padStart(2,'0')+':'+String(seconds%60).padStart(2,'0');if(seconds===0){clearInterval(cart._paypalTimer);cart.querySelector('[data-cart-submission]').value=crypto.randomUUID();clearPayPalState();notify('Der Zahlungsvorgang ist abgelaufen. Bitte starten Sie ihn erneut.',true);}};tick();cart._paypalTimer=setInterval(tick,1000);
        }
        async function loadPayPal() {
            if(paypalSession)return paypalSession;if(paypalLoading)return paypalLoading;
            setPayPalReady(false);
            paypalLoading=(async function(){
            var config=await postPayment('config',{});if(!config.configured)throw new Error('PayPal ist noch nicht vollständig konfiguriert.');
            if(!window.paypal){await new Promise(function(resolve,reject){var script=document.createElement('script');script.src=config.sdk_url;script.async=true;script.onload=resolve;script.onerror=function(){reject(new Error('PayPal konnte nicht geladen werden.'));};document.head.appendChild(script);});}
            var sdk=await window.paypal.createInstance({clientId:config.client_id,components:['paypal-payments'],pageType:'checkout'});var eligible=await sdk.findEligibleMethods({currencyCode:'EUR'});if(!eligible.isEligible('paypal'))throw new Error('PayPal ist für diese Zahlung nicht verfügbar.');
            paypalSession=sdk.createPayPalOneTimePaymentSession({onApprove:async function(data){if(!paypalState)throw new Error('Der PayPal-Zahlungsvorgang konnte nicht wiederhergestellt werden.');var result=await postPayment('capture',{session_token:paypalState.session_token,provider_order_id:data.orderId});clearPayPalState();window.location.assign(result.confirmation_url);},onCancel:function(){clearPayPalState();notify('Die PayPal-Zahlung wurde abgebrochen. Ihr Warenkorb bleibt erhalten.',true);},onError:function(){clearPayPalState();notify('PayPal konnte die Zahlung nicht abschließen. Ihr Warenkorb bleibt erhalten.',true);}});
            if(paypalSession.hasReturned()){if(!paypalState)throw new Error('Der PayPal-Zahlungsvorgang konnte nicht wiederhergestellt werden.');countdown(paypalState.expires_at);await paypalSession.resume();return paypalSession;}
            setPayPalReady(true);
            return paypalSession;
            }());try{return await paypalLoading;}catch(error){setPayPalReady(false);throw error;}finally{paypalLoading=null;}
        }
        async function startPayPal(values) {
            if(!paypalSession)throw new Error('PayPal wird noch geladen. Bitte versuchen Sie es gleich erneut.');
            var createPromise=postPayment('start',values).then(function(state){storePayPalState(state);countdown(state.expires_at);return {orderId:state.order_id};});
            await paypalSession.start({presentationMode:'auto'},createPromise);
        }
        if(cart.dataset.paypalEnabled==='1')loadPayPal().catch(function(error){notify(error.message,true);});
    });
}());
