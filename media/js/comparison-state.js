(function () {
    'use strict';

    var selector = '.fdshop-card[data-product-id],.fdshop-product[data-product-id]';
    var state = null;
    var categories = {};

    function roots() {
        return Array.from(document.querySelectorAll(selector));
    }

    function categoryFor(root) {
        var category = Number(root.closest('.fdshop-category')?.dataset.fdshopCategory || root.dataset.categoryId || categories[root.dataset.productId] || 0);
        if (!category) {
            try {
                var link = new URL(root.querySelector('a[href]')?.href || '', location.href);
                category = Number(link.searchParams.get('catid') || 0);
            } catch (error) {}
        }
        return category;
    }

    function render(currentRoots) {
        if (!state || !state.enabled) return;
        var token = (window.Joomla && Joomla.getOptions('csrf.token')) || '';
        currentRoots.forEach(function (root) {
            if (root.querySelector('[data-fdshop-compare]')) return;
            var host = root.querySelector('.fdshop-card__media,.fdshop-product__main-image') || root;
            var id = Number(root.dataset.productId);
            var category = categoryFor(root);
            var active = state.product_ids.includes(id);
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'fdshop-compare-action' + (active ? ' is-active' : '');
            button.dataset.fdshopCompare = '';
            button.dataset.compareProductId = String(id);
            button.dataset.compareCategoryId = String(category);
            button.dataset.token = token;
            button.disabled = !category;
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
            button.setAttribute('aria-label', 'Produkt vergleichen: ' + (root.querySelector('.fdshop-card__title,h1')?.textContent.trim() || 'Produkt'));
            button.innerHTML = '<span aria-hidden="true">⇄</span>';
            host.append(button);
        });
        document.dispatchEvent(new CustomEvent('fdshop:comparison-state', { detail: state }));
    }

    function load() {
        var currentRoots = roots();
        var query = new URLSearchParams({ option: 'com_fdshop', task: 'comparison.state', format: 'json' });
        currentRoots.forEach(function (root) { query.append('product_ids[]', root.dataset.productId); });
        fetch('index.php?' + query.toString(), { headers: { Accept: 'application/json' } })
            .then(function (response) { return response.json(); })
            .then(function (response) {
                if (response.success === false) return;
                state = response.data || response;
                categories = state.categories || {};
                render(currentRoots);
            });
    }

    document.addEventListener('DOMContentLoaded', load);
    document.addEventListener('fdshop:product-cards-updated', load);
}());
