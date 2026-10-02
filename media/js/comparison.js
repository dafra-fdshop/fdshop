(function () {
    'use strict';

    var state = null;
    var bar = null;

    function endpoint(task) {
        return 'index.php?option=com_fdshop&task=comparison.' + task + '&format=json';
    }

    function post(task, data, token) {
        var body = new URLSearchParams(data || {});
        body.set(token || ((window.Joomla && Joomla.getOptions('csrf.token')) || ''), '1');
        return fetch(endpoint(task), {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json' },
            body: body
        }).then(function (response) { return response.json(); }).then(function (response) {
            if (response.success === false) throw new Error(response.message);
            return response.data || response;
        });
    }

    function paint(next, message) {
        state = next;
        document.querySelectorAll('[data-fdshop-compare]').forEach(function (button) {
            var active = next.product_ids.includes(Number(button.dataset.compareProductId));
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        if (document.querySelector('[data-comparison-page]')) return;
        if (!bar) {
            bar = document.createElement('aside');
            bar.className = 'fdshop-comparison-bar';
            bar.innerHTML = '<span data-compare-message></span><strong data-compare-count></strong><div class="fdshop-comparison-bar__actions"><a class="btn btn-primary" href="index.php?option=com_fdshop&view=comparison">Zum Vergleich</a><button class="btn btn-outline-light" type="button" data-comparison-bar-clear>Leeren</button></div>';
            document.body.append(bar);
        }
        bar.hidden = !next.count;
        bar.querySelector('[data-compare-message]').textContent = message || 'Produkte zum Vergleich ausgewählt.';
        bar.querySelector('[data-compare-count]').textContent = next.count + ' von ' + next.max + ' Produkten ausgewählt';
    }

    document.addEventListener('fdshop:comparison-state', function (event) { paint(event.detail); });
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-fdshop-compare]');
        if (button) {
            event.preventDefault();
            var id = Number(button.dataset.compareProductId);
            if (state && state.product_ids.includes(id)) {
                post('remove', { product_id: id }, button.dataset.token)
                    .then(function (next) { paint(next, 'Produkt wurde entfernt.'); })
                    .catch(function (error) { alert(error.message); });
                return;
            }
            post('add', { product_id: id, category_id: button.dataset.compareCategoryId }, button.dataset.token)
                .then(function (next) {
                    if (next.requires_confirmation) {
                        if (!confirm('Neuen Vergleich starten? Dein aktueller Vergleich enthält Produkte aus einer anderen Kategorie. Möchtest du ihn leeren und mit diesem Produkt einen neuen Vergleich starten?')) return;
                        return post('add', { product_id: id, category_id: button.dataset.compareCategoryId, replace: 1 }, button.dataset.token);
                    }
                    return next;
                }).then(function (next) {
                    if (next) paint(next, (next.product_name || 'Produkt') + ' wurde zum Vergleich hinzugefügt.');
                }).catch(function (error) { alert(error.message); });
            return;
        }
        var barClear = event.target.closest('[data-comparison-bar-clear]');
        if (barClear) {
            post('clear', {}, (document.querySelector('[data-fdshop-compare]') || {}).dataset?.token)
                .then(function (next) { paint(next); })
                .catch(function (error) { alert(error.message); });
            return;
        }
        var page = document.querySelector('[data-comparison-page]');
        var remove = event.target.closest('[data-comparison-remove]');
        if (remove) post('remove', { product_id: remove.dataset.comparisonRemove }, page.dataset.token).then(function () { location.reload(); });
        var clear = event.target.closest('[data-comparison-clear]');
        if (clear) post('clear', {}, page.dataset.token).then(function () { location.reload(); });
        var article = event.target.closest('[data-comparison-list]');
        if (article && event.target.matches('[data-comparison-activate]')) post('activate', { list_id: article.dataset.comparisonList }, page.dataset.token).then(function () { location.reload(); });
        if (article && event.target.matches('[data-comparison-rename]')) {
            var name = prompt('Neuer Name', article.querySelector('strong').textContent);
            if (name) post('rename', { list_id: article.dataset.comparisonList, name: name }, page.dataset.token).then(function () { location.reload(); });
        }
        if (article && event.target.matches('[data-comparison-delete]') && confirm('Gespeicherten Vergleich löschen?')) post('delete', { list_id: article.dataset.comparisonList }, page.dataset.token).then(function () { location.reload(); });
    });

    document.addEventListener('change', function (event) {
        if (!event.target.matches('[data-comparison-differences]')) return;
        var grid = event.target.closest('[data-comparison-page]').querySelector('.fdshop-comparison-grid');
        var count = Number(getComputedStyle(grid).getPropertyValue('--compare-count'));
        Array.from(grid.querySelectorAll('[data-compare-row]')).forEach(function (label) {
            var cells = [];
            var next = label.nextElementSibling;
            for (var i = 0; i < count && next; i++, next = next.nextElementSibling) cells.push(next);
            var same = new Set(cells.map(function (cell) { return cell.dataset.compareValue.trim().toLowerCase(); })).size === 1;
            label.hidden = event.target.checked && same;
            cells.forEach(function (cell) { cell.hidden = event.target.checked && same; });
        });
    });

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('[data-comparison-save]');
        if (!form) return;
        event.preventDefault();
        post('save', { name: new FormData(form).get('name') }, form.closest('[data-comparison-page]').dataset.token)
            .then(function () { location.reload(); })
            .catch(function (error) { alert(error.message); });
    });
}());
