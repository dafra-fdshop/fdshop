<?php

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Helper\PurchaseHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\HTML\HTMLHelper;

if ($this->filterUiEnabled) {
    HTMLHelper::_('bootstrap.offcanvas', '#fdshop-filter-offcanvas');
}

$sort = (string) $this->state->get('filter.sort', 'name');
$direction = (string) $this->state->get('filter.direction', 'asc');
$selectedSort = $sort . ':' . $direction;
$limit = (int) $this->state->get('list.limit', 24);
$start = (int) $this->state->get('list.start', 0);
$total = (int) $this->pagination->total;
$first = $total > 0 ? $start + 1 : 0;
$last = min($start + $limit, $total);
$resultsText = sprintf('%d–%d von %d', $first, $last, $total);
$filterData = ['facets' => $this->filterFacets, 'active' => $this->activeFilters, 'category_id' => (int) $this->category->id, 'sort' => $sort, 'direction' => $direction, 'limit' => $limit];
?>
<main class="fdshop-category" data-fdshop-category="<?php echo (int) $this->category->id; ?>">
    <?php if ($this->showSearch) : ?><?php echo LayoutHelper::render('search.form', ['id' => 'category-' . (int) $this->category->id], JPATH_COMPONENT_SITE . '/layouts'); ?><?php endif; ?>
    <header class="fdshop-category__header">
        <h1><?php echo $this->escape((string) $this->category->category_name); ?></h1>
        <?php if (trim((string) $this->category->description) !== '') : ?>
            <div class="fdshop-category__description"><?php echo nl2br($this->escape((string) $this->category->description)); ?></div>
        <?php endif; ?>
    </header>

    <?php if ($this->filterUiEnabled) : ?>
    <button class="btn btn-primary fdshop-filter-open" type="button" data-bs-toggle="offcanvas" data-bs-target="#fdshop-filter-offcanvas" aria-controls="fdshop-filter-offcanvas">Produkte filtern</button>
    <div class="offcanvas offcanvas-end fdshop-filter-offcanvas" tabindex="-1" id="fdshop-filter-offcanvas" aria-labelledby="fdshop-filter-offcanvas-title">
        <div class="offcanvas-header"><h2 class="offcanvas-title" id="fdshop-filter-offcanvas-title">Produkte filtern</h2><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Filter schließen"></button></div>
        <div class="offcanvas-body"><?php echo LayoutHelper::render('filter.panel', $filterData + ['suffix' => 'mobile'], JPATH_COMPONENT_SITE . '/layouts'); ?></div>
    </div>
    <?php endif; ?>

    <section class="fdshop-category__results" data-fdshop-filter-results aria-live="polite">

    <?php if ($this->filterChips !== []) : ?>
        <div class="fdshop-filter-chips" aria-label="Aktive Filter">
            <?php foreach ($this->filterChips as $chip) : ?><button type="button" class="fdshop-filter-chip" data-fdshop-filter-remove data-filter-key="<?php echo $this->escape($chip['key']); ?>" data-filter-value="<?php echo $this->escape($chip['value']); ?>" aria-label="Filter <?php echo $this->escape($chip['label']); ?> entfernen"><?php echo $this->escape($chip['label']); ?> <span aria-hidden="true">×</span></button><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form class="fdshop-toolbar" method="get" aria-label="Produktliste steuern">
        <input type="hidden" name="option" value="com_fdshop">
        <input type="hidden" name="view" value="category">
        <input type="hidden" name="id" value="<?php echo (int) $this->category->id; ?>">
        <label><span class="form-label">Sortierung</span><select class="form-select form-select-sm" name="sortdir" data-fdshop-sort>
            <?php foreach ($this->sortOptions as $value => $label) : ?>
                <option value="<?php echo $this->escape($value); ?>"<?php echo $selectedSort === $value ? ' selected' : ''; ?>><?php echo $this->escape($label); ?></option>
            <?php endforeach; ?>
        </select></label>
        <input type="hidden" name="sort" value="<?php echo $this->escape($sort); ?>" data-fdshop-sort-field>
        <input type="hidden" name="dir" value="<?php echo $this->escape($direction); ?>" data-fdshop-sort-direction>
        <?php foreach ($this->activeFilters as $filterKey => $filterValues) : foreach ($filterValues as $filterValue) : ?><input type="hidden" name="fd_filter[<?php echo $this->escape($filterKey); ?>][]" value="<?php echo $this->escape((string) $filterValue); ?>"><?php endforeach; endforeach; ?>
        <label><span class="form-label">Produkte pro Seite</span><select class="form-select form-select-sm" name="limit" data-fdshop-submit>
            <?php foreach ($this->limitOptions as $option) : ?>
                <option value="<?php echo (int) $option; ?>"<?php echo $limit === $option ? ' selected' : ''; ?>><?php echo (int) $option; ?></option>
            <?php endforeach; ?>
        </select></label>
        <span class="fdshop-toolbar__results" data-fdshop-results><?php echo $this->escape($resultsText); ?></span>
        <noscript><button type="submit">Übernehmen</button></noscript>
    </form>

    <?php if ($total > $limit) : ?><nav class="fdshop-pagination fdshop-pagination--top" aria-label="Seitennavigation oben"><?php echo $this->pagination->getPagesLinks(); ?></nav><?php endif; ?>

    <?php if ($this->items === []) : ?>
        <p class="fdshop-category__empty">In dieser Kategorie sind aktuell keine Produkte verfügbar.</p>
    <?php else : ?>
        <div class="fdshop-products">
            <?php foreach ($this->items as $item) : ?><?php echo LayoutHelper::render('product.card', ['item' => $item, 'purchaseEnabled' => $this->purchaseEnabled], JPATH_COMPONENT_SITE . '/layouts'); ?><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($total > $limit) : ?><nav class="fdshop-pagination fdshop-pagination--bottom" aria-label="Seitennavigation unten"><?php echo $this->pagination->getPagesLinks(); ?></nav><?php endif; ?>
    </section>
    <dialog class="fdshop-video" data-fdshop-video-dialog aria-labelledby="fdshop-video-title">
        <div class="fdshop-video__header"><h2 id="fdshop-video-title" data-fdshop-video-title>Produktvideo</h2><button type="button" class="fdshop-video__close" data-fdshop-video-close aria-label="Video schließen">×</button></div>
        <div class="fdshop-video__content" data-fdshop-video-content></div>
    </dialog>
    <?php if ($this->purchaseEnabled && PurchaseHelper::claimModal()) : ?><?php echo LayoutHelper::render('purchase.modal', [], JPATH_COMPONENT_SITE . '/layouts'); ?><?php endif; ?>
</main>
