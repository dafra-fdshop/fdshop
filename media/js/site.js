(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var category = document.querySelector('.fdshop-category');
        if (!category) return;

        var toolbar = category.querySelector('.fdshop-toolbar');
        var sortSelect = category.querySelector('[data-fdshop-sort]');
        var sortField = category.querySelector('[data-fdshop-sort-field]');
        var sortDirection = category.querySelector('[data-fdshop-sort-direction]');
        if (toolbar && sortSelect && sortField && sortDirection) {
            sortSelect.addEventListener('change', function () {
                var value = sortSelect.value.split(':');
                sortField.value = value[0] || 'name';
                sortDirection.value = value[1] || 'asc';
                toolbar.submit();
            });
            category.querySelectorAll('[data-fdshop-submit]').forEach(function (field) {
                field.addEventListener('change', function () { toolbar.submit(); });
            });
        }

        var dialog = category.querySelector('[data-fdshop-video-dialog]');
        var content = category.querySelector('[data-fdshop-video-content]');
        var title = category.querySelector('[data-fdshop-video-title]');
        if (!dialog || !content || !title) return;

        category.querySelectorAll('[data-fdshop-video]').forEach(function (button) {
            button.addEventListener('click', function () {
                var frame = document.createElement('iframe');
                frame.src = button.dataset.fdshopVideo;
                frame.title = 'Produktvideo: ' + (button.dataset.productName || 'FDShop-Produkt');
                frame.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture';
                frame.allowFullscreen = true;
                frame.loading = 'lazy';
                content.replaceChildren(frame);
                title.textContent = button.dataset.productName || 'Produktvideo';
                dialog.showModal();
            });
        });
        var closeButton = category.querySelector('[data-fdshop-video-close]');
        if (closeButton) closeButton.addEventListener('click', function () { dialog.close(); });
        dialog.addEventListener('close', function () { content.replaceChildren(); });
        dialog.addEventListener('click', function (event) { if (event.target === dialog) dialog.close(); });
    });
}());
