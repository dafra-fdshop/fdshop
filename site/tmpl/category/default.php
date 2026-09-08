<?php

defined('_JEXEC') or die;

$sort = (string) $this->state->get('filter.sort', 'name');
$direction = (string) $this->state->get('filter.direction', 'asc');
$selectedSort = $sort . ':' . $direction;
$limit = (int) $this->state->get('list.limit', 24);
$start = (int) $this->state->get('list.start', 0);
$total = (int) $this->pagination->total;
$first = $total > 0 ? $start + 1 : 0;
$last = min($start + $limit, $total);
$resultsText = sprintf('%d–%d von %d', $first, $last, $total);
?>
<main class="fdshop-category" data-fdshop-category="<?php echo (int) $this->category->id; ?>">
    <header class="fdshop-category__header">
        <h1><?php echo $this->escape((string) $this->category->category_name); ?></h1>
        <?php if (trim((string) $this->category->description) !== '') : ?>
            <div class="fdshop-category__description"><?php echo nl2br($this->escape((string) $this->category->description)); ?></div>
        <?php endif; ?>
    </header>

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
            <?php foreach ($this->items as $item) : ?>
                <?php
                $factValues = [
                    'NEM' => (float) $item->nem > 0
                        ? rtrim(rtrim(number_format((float) $item->nem, 3, ',', '.'), '0'), ',') . ' g'
                        : '-',
                    'Schusszahl' => (float) $item->shot_count > 0
                        ? rtrim(rtrim(number_format((float) $item->shot_count, 3, ',', '.'), '0'), ',')
                        : '-',
                    'Kaliber' => trim((string) $item->caliber) !== '' && (float) $item->caliber !== 0.0 ? (string) $item->caliber : '-',
                    'Brenndauer' => trim((string) $item->burn_time) !== '' && (float) $item->burn_time !== 0.0 ? (string) $item->burn_time : '-',
                    'Steighöhe' => trim((string) $item->rise_height) !== '' && (float) $item->rise_height !== 0.0 ? (string) $item->rise_height : '-',
                ];
                $factIcons = [
                    'NEM' => 'icon_nem.svg',
                    'Schusszahl' => 'icon_anzahl.svg',
                    'Kaliber' => 'icon_durchm.svg',
                    'Brenndauer' => 'icon_zeit.svg',
                    'Steighöhe' => 'icon_hoehe.svg',
                ];
                ?>
                <article class="fdshop-card" data-product-id="<?php echo (int) $item->id; ?>" data-price="<?php echo $this->escape((string) $item->current_price); ?>">
                    <div class="fdshop-card__media">
                        <a class="fdshop-card__image-link" href="<?php echo $this->escape($item->detail_url); ?>" aria-label="Details zu <?php echo $this->escape((string) $item->product_name); ?>">
                            <img src="<?php echo $this->escape($item->image_url); ?>" alt="<?php echo $this->escape((string) $item->product_name); ?>" loading="lazy" width="400" height="400">
                        </a>
                        <div class="fdshop-card__ribbons" aria-label="Produktkennzeichnungen">
                            <?php if ((int) $item->ribbon_new === 1) : ?><span class="fdshop-ribbon fdshop-ribbon--new">Neu</span><?php endif; ?>
                            <?php if ((int) $item->ribbon_hot === 1) : ?><span class="fdshop-ribbon fdshop-ribbon--hot">Hot</span><?php endif; ?>
                            <?php if ((int) $item->ribbon_bundle === 1) : ?><span class="fdshop-ribbon fdshop-ribbon--bundle">Bundle</span><?php endif; ?>
                        </div>
                    </div>
                    <div class="fdshop-card__body">
                        <h2 class="fdshop-card__title"><a href="<?php echo $this->escape($item->detail_url); ?>"><?php echo $this->escape((string) $item->product_name); ?></a></h2>
                        <?php if (trim((string) $item->short_description) !== '') : ?><p class="fdshop-card__description"><?php echo $this->escape((string) $item->short_description); ?></p><?php endif; ?>
                        <div class="fdshop-card__actions">
                            <a class="btn btn-primary btn-sm" href="<?php echo $this->escape($item->detail_url); ?>">Details</a>
                            <?php if ($item->media['video'] !== null) : ?><button class="btn btn-dark btn-sm" type="button" data-fdshop-video="<?php echo $this->escape($item->media['video']); ?>" data-product-name="<?php echo $this->escape((string) $item->product_name); ?>" aria-label="Produktvideo zu <?php echo $this->escape((string) $item->product_name); ?> ansehen" title="Produktvideo ansehen"><i class="fa-solid fa-video" aria-hidden="true"></i></button><?php endif; ?>
                            <p class="fdshop-stock <?php echo $this->escape($item->stock_class); ?>"><strong><?php echo $this->escape((string) $item->in_stock); ?></strong></p>
                        </div>
                        <div class="fdshop-card__info">
                            <dl class="fdshop-card__facts" aria-label="Technische Produktdaten">
                                <?php foreach ($factValues as $factLabel => $factValue) : ?>
                                    <div class="fdshop-card__fact" title="<?php echo $this->escape($factLabel); ?>">
                                        <dt class="visually-hidden"><?php echo $this->escape($factLabel); ?></dt>
                                        <dd aria-label="<?php echo $this->escape($factLabel . ': ' . $factValue); ?>">
                                            <img src="<?php echo $this->escape(\Joomla\CMS\Uri\Uri::root(true) . '/media/com_fdshop/images/product-facts/' . $factIcons[$factLabel]); ?>" alt="" aria-hidden="true" width="28" height="28" loading="lazy">
                                            <span><?php echo $this->escape($factValue); ?></span>
                                        </dd>
                                    </div>
                                <?php endforeach; ?>
                            </dl>
                            <div class="fdshop-card__price" data-effective-price="<?php echo $this->escape((string) $item->current_price); ?>">
                                <?php if ($item->has_discount) : ?><span class="fdshop-card__regular-price"><?php echo $this->escape($item->regular_price_formatted); ?></span><?php endif; ?>
                                <strong><?php echo $this->escape($item->price_formatted); ?></strong>
                                <small>inkl. MwSt.</small>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($total > $limit) : ?><nav class="fdshop-pagination fdshop-pagination--bottom" aria-label="Seitennavigation unten"><?php echo $this->pagination->getPagesLinks(); ?></nav><?php endif; ?>
    <dialog class="fdshop-video" data-fdshop-video-dialog aria-labelledby="fdshop-video-title">
        <div class="fdshop-video__header"><h2 id="fdshop-video-title" data-fdshop-video-title>Produktvideo</h2><button type="button" class="fdshop-video__close" data-fdshop-video-close aria-label="Video schließen">×</button></div>
        <div class="fdshop-video__content" data-fdshop-video-content></div>
    </dialog>
</main>
