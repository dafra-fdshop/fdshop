(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var category = document.querySelector('.fdshop-category');
        var filterRequest = null;
        var refreshFilteredCategory = function (form, pushHistory) {
            if (!category || !form) return;
            var url = new URL(window.location.href);
            url.searchParams.delete('limitstart');
            Array.from(url.searchParams.keys()).forEach(function (key) { if (key.indexOf('fd_filter[') === 0) url.searchParams.delete(key); });
            new FormData(form).forEach(function (value, key) { if (!['option', 'view', 'id'].includes(key) && value !== '') url.searchParams.append(key, value); });
            if (filterRequest) filterRequest.abort();
            filterRequest = new AbortController();
            category.setAttribute('aria-busy', 'true');
            fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: filterRequest.signal })
                .then(function (response) { if (!response.ok) throw new Error('Filter request failed'); return response.text(); })
                .then(function (html) {
                    var nextDocument = new DOMParser().parseFromString(html, 'text/html');
                    var next = nextDocument.querySelector('.fdshop-category');
                    if (!next) throw new Error('Filter response is incomplete');
                    var currentResults = category.querySelector('[data-fdshop-filter-results]');
                    var nextResults = next.querySelector('[data-fdshop-filter-results]');
                    var currentMobile = category.querySelector('.fdshop-filter-offcanvas .offcanvas-body');
                    var nextMobile = next.querySelector('.fdshop-filter-offcanvas .offcanvas-body');
                    var currentModule = document.querySelector('[data-fdshop-filter-module]');
                    var nextModule = nextDocument.querySelector('[data-fdshop-filter-module]');
                    if (!currentResults || !nextResults || !currentMobile || !nextMobile || (currentModule && !nextModule)) throw new Error('Filter fragments are incomplete');
                    currentResults.replaceWith(nextResults);
                    currentMobile.replaceWith(nextMobile);
                    if (currentModule && nextModule) currentModule.replaceWith(nextModule);
                    category.removeAttribute('aria-busy');
                    if (pushHistory) window.history.pushState({}, '', url.toString());
                    bindFilters();
                    bindCategoryControls();
                })
                .catch(function (error) { category.removeAttribute('aria-busy'); if (error.name !== 'AbortError') category.dispatchEvent(new CustomEvent('fdshop:filter-error')); });
        };
        var bindFilters = function () {
            if (!category) return;
            document.querySelectorAll('[data-fdshop-filter-form]').forEach(function (form) {
                if (form.dataset.bound === '1') return;
                form.dataset.bound = '1';
                form.addEventListener('change', function () { refreshFilteredCategory(form, true); });
                var reset = form.querySelector('[data-fdshop-filter-reset]');
                if (reset) reset.addEventListener('click', function () { form.querySelectorAll('input[type="checkbox"]').forEach(function (box) { box.checked = false; }); refreshFilteredCategory(form, true); });
            });
            category.querySelectorAll('[data-fdshop-filter-remove]').forEach(function (button) {
                if (button.dataset.bound === '1') return;
                button.dataset.bound = '1';
                button.addEventListener('click', function () {
                    var form = document.querySelector('[data-fdshop-filter-module] [data-fdshop-filter-form]') || category.querySelector('[data-fdshop-filter-form]');
                    document.querySelectorAll('input[name="fd_filter[' + button.dataset.filterKey + '][]"]').forEach(function (box) { if (box.value === button.dataset.filterValue) box.checked = false; });
                    refreshFilteredCategory(form, true);
                });
            });
        };
        var bindCategoryControls = function () {
            var toolbar = category ? category.querySelector('.fdshop-toolbar') : null;
            var sortSelect = category ? category.querySelector('[data-fdshop-sort]') : null;
            var sortField = category ? category.querySelector('[data-fdshop-sort-field]') : null;
            var sortDirection = category ? category.querySelector('[data-fdshop-sort-direction]') : null;
            if (toolbar && sortSelect && sortField && sortDirection && toolbar.dataset.bound !== '1') {
                toolbar.dataset.bound = '1';
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
        };
        bindFilters();
        bindCategoryControls();
        window.addEventListener('popstate', function () { window.location.reload(); });

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

(function () {
    'use strict';
    var root = document.querySelector('[data-fdshop-package-root]');
    var select = root ? root.querySelector('[data-fdshop-package-select]') : null;
    if (!root || !select) return;
    var name = root.querySelector('[data-fdshop-package-name]');
    var price = root.querySelector('[data-fdshop-package-price]');
    var regular = root.querySelector('[data-fdshop-package-regular]');
    var purchase = root.querySelector('[data-fdshop-purchase]');
    var quantity = purchase ? purchase.querySelector('[data-purchase-quantity]') : null;
    var formatPrice = function (value) { return Number(value).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' EUR'; };
    var formatFact = function (value, suffix) { var number = Number(value); return number > 0 ? number.toLocaleString('de-DE', { maximumFractionDigits: 3 }) + suffix : '-'; };
    select.addEventListener('change', function () {
        var packaged = select.value === 'package';
        name.textContent = packaged ? root.dataset.packageName : root.dataset.pieceName;
        price.textContent = formatPrice(packaged ? root.dataset.packagePrice : root.dataset.piecePrice);
        var regularValue = Number(packaged ? root.dataset.packageRegularPrice : root.dataset.pieceRegularPrice);
        var currentValue = Number(packaged ? root.dataset.packagePrice : root.dataset.piecePrice);
        regular.textContent = formatPrice(regularValue);
        regular.hidden = regularValue <= currentValue;
        if (purchase) purchase.dataset.unitVariant = packaged ? 'package' : 'piece';
        var multiplier = packaged ? Number(root.dataset.packageQuantity) : 1;
        var nem = root.querySelector('[data-fdshop-package-fact="nem"]');
        var shots = root.querySelector('[data-fdshop-package-fact="shots"]');
        if (nem) nem.textContent = formatFact(Number(root.dataset.pieceNem) * multiplier, ' g');
        if (shots) shots.textContent = formatFact(Number(root.dataset.pieceShots) * multiplier, '');
        if (quantity) { quantity.value = '1'; quantity.min = '1'; quantity.step = '1'; }
    });
}());
