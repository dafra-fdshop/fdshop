(function () {
    'use strict';
    function load() {
        var roots = Array.from(document.querySelectorAll('.fdshop-card[data-product-id],.fdshop-product[data-product-id],.fdshop-comparison-product[data-product-id]'));
        if (!roots.length) return;
        var params = new URLSearchParams({ option: 'com_fdshop', task: 'favorite.states', format: 'json' });
        roots.forEach(function (root) { params.append('product_ids[]', root.dataset.productId); });
        fetch('index.php?' + params.toString(), { headers: { Accept: 'application/json' } })
            .then(function (response) { return response.json(); })
            .then(function (response) {
                if (response.success === false) return;
                var data = response.data || response;
                if (!data.enabled) return;
                var token = (window.Joomla && Joomla.getOptions('csrf.token')) || '';
                roots.forEach(function (root) {
                    var id = root.dataset.productId;
                    var host = root.querySelector('.fdshop-card__media,.fdshop-product__main-image') || root;
                    if (host.querySelector('[data-fdshop-favorite]')) return;
                    var active = data.states && data.states[id];
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'fdshop-favorite' + (active ? ' is-active' : '');
                    button.dataset.fdshopFavorite = '';
                    button.dataset.favoriteProductId = id;
                    button.dataset.guest = data.guest ? '1' : '0';
                    button.dataset.toggleUrl = 'index.php?option=com_fdshop&task=favorite.toggle&format=json';
                    button.dataset.listsUrl = 'index.php?option=com_fdshop&task=favorite.lists&format=json';
                    button.dataset.saveUrl = 'index.php?option=com_fdshop&task=favorite.saveMemberships&format=json';
                    button.dataset.token = token;
                    button.setAttribute('aria-pressed', active ? 'true' : 'false');
                    button.setAttribute('aria-label', (active ? 'Aus Favoriten entfernen' : 'Zu Favoriten hinzufügen') + ': ' + (root.querySelector('.fdshop-card__title,.fdshop-product__title,h1,strong')?.textContent.trim() || 'Produkt'));
                    button.title = 'Favorit';
                    button.innerHTML = '<span aria-hidden="true">♥</span>';
                    host.append(button);
                });
            });
    }
    document.addEventListener('DOMContentLoaded', load);
    document.addEventListener('fdshop:product-cards-updated', load);
}());
