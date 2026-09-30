(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        var provider = document.getElementById('jform_provider');
        var live = document.querySelector('input[name="jform[paypal_mode]"][value="live"]');
        var confirmation = document.getElementById('jform_paypal_live_confirmation');
        var panel = document.querySelector('[data-paypal-configuration]');
        if (!provider || !panel) return;
        var refresh = function () {
            panel.hidden = provider.value !== 'paypal';
            if (confirmation) confirmation.required = !panel.hidden && !!live && live.checked && panel.dataset.currentMode !== 'live';
        };
        provider.addEventListener('change', refresh);
        document.querySelectorAll('input[name="jform[paypal_mode]"]').forEach(function (input) { input.addEventListener('change', refresh); });
        refresh();
        var copy = document.querySelector('[data-copy-paypal-webhook]');
        var url = document.getElementById('fdshop-paypal-webhook-url');
        if (copy && url) copy.addEventListener('click', function () { navigator.clipboard.writeText(url.value); });
    });
}());
