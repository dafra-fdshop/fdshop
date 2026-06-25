(function () {
    'use strict';

    function formatPrice(value) {
        if (value === null || value === undefined || value === '') {
            return '—';
        }

        var number = Number(value);

        if (Number.isNaN(number)) {
            return '—';
        }

        return number.toLocaleString('de-DE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' €';
    }

    function getJsonData(response) {
        if (response && response.data) {
            return response.data;
        }

        return response || null;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var productBox = document.getElementById('fdshop-bundle-products');
        var skuInput = document.getElementById('bundle-product-sku');
        var addProductButton = document.getElementById('bundle-product-add');
        var productTable = document.getElementById('bundle-product-table');
        var discountTable = document.getElementById('bundle-discount-table');
        var addDiscountButton = document.getElementById('bundle-discount-add');

        if (productBox && skuInput && addProductButton && productTable) {
            addProductButton.addEventListener('click', function () {
                var sku = skuInput.value.trim();

                if (sku === '') {
                    return;
                }

                var lookupUrl = productBox.dataset.lookupUrl || '';
                var token = productBox.dataset.token || '';
                var url = lookupUrl + '&sku=' + encodeURIComponent(sku);

                if (token !== '') {
                    url += '&' + encodeURIComponent(token) + '=1';
                }

                fetch(url, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                    .then(function (result) {
                        return result.json();
                    })
                    .then(function (response) {
                        if (response.success === false) {
                            window.alert(response.message || 'Produkt wurde nicht gefunden.');
                            return;
                        }

                        var product = getJsonData(response);

                        if (!product || !product.product_id) {
                            window.alert('Produkt wurde nicht gefunden.');
                            return;
                        }

                        if (productTable.querySelector('tr[data-product-id="' + String(product.product_id) + '"]')) {
                            skuInput.value = '';
                            return;
                        }

                        var tbody = productTable.querySelector('tbody');
                        var row = document.createElement('tr');
                        row.dataset.productId = String(product.product_id);

                        row.innerHTML = ''
                            + '<td>'
                            + String(product.product_name || '')
                            + '<input type="hidden" name="jform[product_ids][]" value="' + String(product.product_id) + '">'
                            + '</td>'
                            + '<td>' + String(product.sku || '') + '</td>'
                            + '<td>' + formatPrice(product.price_gross) + '</td>'
                            + '<td class="text-center"><button type="button" class="btn btn-sm btn-danger bundle-product-remove">Entfernen</button></td>';

                        tbody.appendChild(row);
                        skuInput.value = '';
                    })
                    .catch(function () {
                        window.alert('Produktlookup konnte nicht ausgeführt werden.');
                    });
            });

            productTable.addEventListener('click', function (event) {
                var button = event.target.closest('.bundle-product-remove');

                if (!button) {
                    return;
                }

                var row = button.closest('tr');

                if (row) {
                    row.remove();
                }
            });
        }

        if (discountTable && addDiscountButton) {
            addDiscountButton.addEventListener('click', function () {
                var tbody = discountTable.querySelector('tbody');
                var index = tbody.querySelectorAll('tr').length;
                var row = document.createElement('tr');

                row.innerHTML = ''
                    + '<td>'
                    + '<input type="number" name="jform[discount_rules][' + index + '][min_quantity]" value="1" min="1" step="1" class="form-control">'
                    + '<input type="hidden" name="jform[discount_rules][' + index + '][ordering]" value="' + (index + 1) + '">'
                    + '</td>'
                    + '<td><input type="number" name="jform[discount_rules][' + index + '][discount_percent]" value="0" min="0" step="0.01" class="form-control"></td>'
                    + '<td class="text-center"><button type="button" class="btn btn-sm btn-danger bundle-discount-remove">Entfernen</button></td>';

                tbody.appendChild(row);
            });

            discountTable.addEventListener('click', function (event) {
                var button = event.target.closest('.bundle-discount-remove');

                if (!button) {
                    return;
                }

                var row = button.closest('tr');

                if (row && discountTable.querySelectorAll('tbody tr').length > 1) {
                    row.remove();
                }
            });
        }
    });
})();
