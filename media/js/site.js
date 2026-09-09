(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var category = document.querySelector('.fdshop-category');
        var toolbar = category ? category.querySelector('.fdshop-toolbar') : null;
        var sortSelect = category ? category.querySelector('[data-fdshop-sort]') : null;
        var sortField = category ? category.querySelector('[data-fdshop-sort-field]') : null;
        var sortDirection = category ? category.querySelector('[data-fdshop-sort-direction]') : null;
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

        var dialog = category ? category.querySelector('[data-fdshop-video-dialog]') : null;
        var content = category ? category.querySelector('[data-fdshop-video-content]') : null;
        var title = category ? category.querySelector('[data-fdshop-video-title]') : null;
        if (dialog && content && title) category.querySelectorAll('[data-fdshop-video]').forEach(function (button) {
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
        if (dialog && content) {
            var closeButton = category.querySelector('[data-fdshop-video-close]');
            if (closeButton) closeButton.addEventListener('click', function () { dialog.close(); });
            dialog.addEventListener('close', function () { content.replaceChildren(); });
            dialog.addEventListener('click', function (event) { if (event.target === dialog) dialog.close(); });
        }

        var product = document.querySelector('.fdshop-product');
        if (!product) return;

        var mainImage = product.querySelector('[data-fdshop-main-image]');
        product.querySelectorAll('[data-fdshop-thumbnail]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!mainImage) return;
                mainImage.src = button.dataset.fdshopThumbnail;
                product.querySelectorAll('[data-fdshop-thumbnail]').forEach(function (thumbnail) {
                    var active = thumbnail === button;
                    thumbnail.classList.toggle('is-active', active);
                    thumbnail.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
            });
        });

        var playButton = product.querySelector('[data-fdshop-video-inline]');
        if (playButton) playButton.addEventListener('click', function () {
            var frame = document.createElement('iframe');
            frame.src = playButton.dataset.fdshopVideoInline;
            frame.title = 'Produktvideo: ' + (playButton.dataset.productName || 'FDShop-Produkt');
            frame.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture';
            frame.allowFullscreen = true;
            playButton.replaceWith(frame);
        });

        var detailDialog = product.querySelector('[data-fdshop-detail-video-dialog]');
        var detailContent = product.querySelector('[data-fdshop-detail-video-content]');
        var detailTitle = product.querySelector('[data-fdshop-detail-video-title]');
        if (detailDialog && detailContent && detailTitle) {
            product.querySelectorAll('[data-fdshop-detail-video]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var frame = document.createElement('iframe');
                    frame.src = button.dataset.fdshopDetailVideo;
                    frame.title = 'Produktvideo: ' + (button.dataset.productName || 'FDShop-Produkt');
                    frame.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture';
                    frame.allowFullscreen = true;
                    detailContent.replaceChildren(frame);
                    detailTitle.textContent = button.dataset.productName || 'Produktvideo';
                    detailDialog.showModal();
                });
            });
            var detailClose = product.querySelector('[data-fdshop-detail-video-close]');
            if (detailClose) detailClose.addEventListener('click', function () { detailDialog.close(); });
            detailDialog.addEventListener('close', function () { detailContent.replaceChildren(); });
            detailDialog.addEventListener('click', function (event) { if (event.target === detailDialog) detailDialog.close(); });
        }
    });
}());
