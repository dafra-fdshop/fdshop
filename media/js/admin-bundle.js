(function () {
    'use strict';

    function formatPrice(value, currency) {
        if (value === null || value === undefined || value === '') {
            return '—';
        }

        var number = Number(value);

        if (Number.isNaN(number)) {
            return '—';
        }

        var currencyCode = String(currency || 'EUR').trim().toUpperCase();

        try {
            return number.toLocaleString('de-DE', {
                style: 'currency',
                currency: currencyCode,
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        } catch (error) {
            return number.toLocaleString('de-DE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' ' + currencyCode;
        }
    }

    function getJsonData(response) {
        if (response && response.data) {
            return response.data;
        }

        return response || null;
    }

    function appendProductRow(productTable, product) {
        var tbody = productTable.querySelector('tbody');
        var row = document.createElement('tr');
        var nameCell = document.createElement('td');
        var skuCell = document.createElement('td');
        var priceCell = document.createElement('td');
        var actionCell = document.createElement('td');
        var hiddenInput = document.createElement('input');
        var removeButton = document.createElement('button');

        row.dataset.productId = String(product.product_id);
        nameCell.appendChild(document.createTextNode(String(product.product_name || '')));

        hiddenInput.type = 'hidden';
        hiddenInput.name = 'jform[product_ids][]';
        hiddenInput.value = String(product.product_id);
        nameCell.appendChild(hiddenInput);

        skuCell.textContent = String(product.sku || '');
        priceCell.textContent = formatPrice(product.current_sale_price, product.currency);

        actionCell.className = 'text-center';
        removeButton.type = 'button';
        removeButton.className = 'btn btn-sm btn-danger bundle-product-remove';
        removeButton.textContent = 'Entfernen';
        actionCell.appendChild(removeButton);

        row.appendChild(nameCell);
        row.appendChild(skuCell);
        row.appendChild(priceCell);
        row.appendChild(actionCell);
        tbody.appendChild(row);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var productBox = document.getElementById('fdshop-bundle-products');
        var skuInput = document.getElementById('bundle-product-sku');
        var addProductButton = document.getElementById('bundle-product-add');
        var productTable = document.getElementById('bundle-product-table');
        var productSuggestions = document.getElementById('bundle-product-suggestions');
        var discountTable = document.getElementById('bundle-discount-table');
        var addDiscountButton = document.getElementById('bundle-discount-add');

        if (productBox && skuInput && addProductButton && productTable) {
            var searchTimer = null;
            var searchRequest = null;

            function clearSuggestions() {
                if (!productSuggestions) {
                    return;
                }

                productSuggestions.replaceChildren();
                productSuggestions.classList.add('d-none');
                skuInput.setAttribute('aria-expanded', 'false');
            }

            function renderSuggestions(products) {
                clearSuggestions();

                if (!productSuggestions || !Array.isArray(products) || products.length === 0) {
                    return;
                }

                products.forEach(function (product) {
                    var option = document.createElement('button');
                    var label = String(product.sku || '') + ' - ' + String(product.product_name || '');

                    if (Number(product.is_active) === 0) {
                        label += ' (inaktiv)';
                    }

                    option.type = 'button';
                    option.className = 'list-group-item list-group-item-action text-start';
                    option.setAttribute('role', 'option');
                    option.dataset.productId = String(product.product_id || '');
                    option.dataset.sku = String(product.sku || '');
                    option.textContent = label;

                    option.addEventListener('click', function () {
                        skuInput.value = option.dataset.sku;
                        skuInput.dataset.selectedProductId = option.dataset.productId;
                        clearSuggestions();
                        skuInput.focus();
                    });

                    productSuggestions.appendChild(option);
                });

                productSuggestions.classList.remove('d-none');
                skuInput.setAttribute('aria-expanded', 'true');
            }

            skuInput.addEventListener('input', function () {
                var prefix = skuInput.value.trim();
                var searchUrl = productBox.dataset.searchUrl || '';
                var token = productBox.dataset.token || '';

                delete skuInput.dataset.selectedProductId;
                window.clearTimeout(searchTimer);

                if (searchRequest) {
                    searchRequest.abort();
                    searchRequest = null;
                }

                if (prefix.length < 2 || searchUrl === '') {
                    clearSuggestions();
                    return;
                }

                searchTimer = window.setTimeout(function () {
                    var url = searchUrl + '&q=' + encodeURIComponent(prefix);
                    searchRequest = new AbortController();

                    if (token !== '') {
                        url += '&' + encodeURIComponent(token) + '=1';
                    }

                    fetch(url, {
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json'
                        },
                        signal: searchRequest.signal
                    })
                        .then(function (result) {
                            return result.json();
                        })
                        .then(function (response) {
                            if (response.success === false) {
                                clearSuggestions();
                                return;
                            }

                            renderSuggestions(getJsonData(response) || []);
                        })
                        .catch(function (error) {
                            if (error.name !== 'AbortError') {
                                clearSuggestions();
                            }
                        });
                }, 250);
            });

            document.addEventListener('click', function (event) {
                if (!productBox.contains(event.target)) {
                    clearSuggestions();
                }
            });

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

                        appendProductRow(productTable, product);
                        skuInput.value = '';
                        delete skuInput.dataset.selectedProductId;
                        clearSuggestions();
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
