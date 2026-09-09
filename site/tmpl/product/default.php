<?php

defined('_JEXEC') or die;

$item = $this->item;
$mainImage = $this->images[0] ?? $this->placeholderImage;
$factValues = [
    'NEM' => (float) $item->nem > 0 ? rtrim(rtrim(number_format((float) $item->nem, 3, ',', '.'), '0'), ',') . ' g' : '-',
    'Schusszahl' => (float) $item->shot_count > 0 ? rtrim(rtrim(number_format((float) $item->shot_count, 3, ',', '.'), '0'), ',') : '-',
    'Kaliber' => trim((string) $item->caliber) !== '' && (float) $item->caliber !== 0.0 ? (string) $item->caliber : '-',
    'Brenndauer' => trim((string) $item->burn_time) !== '' && (float) $item->burn_time !== 0.0 ? (string) $item->burn_time : '-',
    'Steighöhe' => trim((string) $item->rise_height) !== '' && (float) $item->rise_height !== 0.0 ? (string) $item->rise_height : '-',
];
$factIcons = ['NEM' => 'icon_nem.svg', 'Schusszahl' => 'icon_anzahl.svg', 'Kaliber' => 'icon_durchm.svg', 'Brenndauer' => 'icon_zeit.svg', 'Steighöhe' => 'icon_hoehe.svg'];
?>
<main class="fdshop-product" data-product-id="<?php echo (int) $item->id; ?>">
    <div class="fdshop-product__overview">
        <section class="fdshop-product__gallery" aria-label="Produktbilder">
            <div class="fdshop-product__main-image">
                <img src="<?php echo $this->escape($mainImage); ?>" alt="<?php echo $this->escape((string) $item->product_name); ?>" width="700" height="700" data-fdshop-main-image>
                <div class="fdshop-card__ribbons" aria-label="Produktkennzeichnungen">
                    <?php if ((int) $item->ribbon_new === 1) : ?><span class="fdshop-ribbon fdshop-ribbon--new">Neu</span><?php endif; ?>
                    <?php if ((int) $item->ribbon_hot === 1) : ?><span class="fdshop-ribbon fdshop-ribbon--hot">Hot</span><?php endif; ?>
                    <?php if ((int) $item->ribbon_bundle === 1) : ?><span class="fdshop-ribbon fdshop-ribbon--bundle">Bundle</span><?php endif; ?>
                </div>
            </div>
            <?php if (count($this->images) > 1) : ?>
                <div class="fdshop-product__thumbnails" aria-label="Weitere Produktbilder">
                    <?php foreach ($this->images as $index => $image) : ?>
                        <button type="button" class="fdshop-product__thumbnail<?php echo $index === 0 ? ' is-active' : ''; ?>" data-fdshop-thumbnail="<?php echo $this->escape($image); ?>" aria-label="Produktbild <?php echo $index + 1; ?> anzeigen" aria-pressed="<?php echo $index === 0 ? 'true' : 'false'; ?>"><img src="<?php echo $this->escape($image); ?>" alt="" width="90" height="90" loading="lazy"></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <section class="fdshop-product__details">
            <h1><?php echo $this->escape((string) $item->product_name); ?></h1>
            <?php if ($item->manufacturer_url !== '') : ?><p class="fdshop-product__manufacturer">Hersteller: <a href="<?php echo $this->escape($item->manufacturer_url); ?>"><?php echo $this->escape((string) $item->manufacturer_name); ?></a></p><?php endif; ?>
            <?php if (trim((string) $item->short_description) !== '') : ?><div class="fdshop-product__short-description"><?php echo nl2br($this->escape((string) $item->short_description)); ?></div><?php endif; ?>
            <dl class="fdshop-product__facts" aria-label="Technische Produktdaten">
                <?php foreach ($factValues as $label => $value) : ?><div class="fdshop-product__fact" title="<?php echo $this->escape($label); ?>"><dt class="visually-hidden"><?php echo $this->escape($label); ?></dt><dd aria-label="<?php echo $this->escape($label . ': ' . $value); ?>"><img src="<?php echo $this->escape(\Joomla\CMS\Uri\Uri::root(true) . '/media/com_fdshop/images/product-facts/' . $factIcons[$label]); ?>" alt="" aria-hidden="true" width="42" height="42"><span><?php echo $this->escape($value); ?></span></dd></div><?php endforeach; ?>
            </dl>
            <?php if ($item->media['video'] !== null) : ?>
                <div class="fdshop-product__video" data-fdshop-product-video><button type="button" class="fdshop-product__video-play" data-fdshop-video-inline="<?php echo $this->escape($item->media['video']); ?>" data-product-name="<?php echo $this->escape((string) $item->product_name); ?>" aria-label="Produktvideo zu <?php echo $this->escape((string) $item->product_name); ?> abspielen"><img src="https://i.ytimg.com/vi/<?php echo $this->escape($item->media['video_id']); ?>/hqdefault.jpg" alt="Video-Vorschaubild zu <?php echo $this->escape((string) $item->product_name); ?>" width="480" height="360" loading="lazy"><span class="fdshop-product__play-icon" aria-hidden="true"><i class="fa-solid fa-play"></i></span></button></div>
            <?php endif; ?>
            <div class="fdshop-product__purchase-zone" aria-label="Preis und Verfügbarkeit">
                <p class="fdshop-stock <?php echo $this->escape($item->stock_class); ?>"><strong><?php echo $this->escape((string) $item->in_stock); ?></strong></p>
                <div class="fdshop-product__price" data-effective-price="<?php echo $this->escape((string) $item->current_price); ?>"><?php if ($item->has_discount) : ?><span class="fdshop-product__regular-price"><?php echo $this->escape($item->regular_price_formatted); ?></span><?php endif; ?><strong><?php echo $this->escape($item->price_formatted); ?></strong><small>inkl. MwSt.</small></div>
            </div>
        </section>
    </div>
    <?php if (trim((string) $item->description) !== '') : ?><section class="fdshop-product__description" aria-labelledby="fdshop-product-description-heading"><h2 id="fdshop-product-description-heading">Produktbeschreibung</h2><div><?php echo \Joomla\CMS\HTML\HTMLHelper::_('content.prepare', (string) $item->description); ?></div></section><?php endif; ?>
</main>
