<?php

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Helper\PurchaseHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

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
<main class="fdshop-product" data-product-id="<?php echo (int) $item->id; ?>" data-fdshop-package-root data-piece-name="<?php echo $this->escape((string) $item->product_name); ?>" data-piece-price="<?php echo $this->escape((string) $item->current_price); ?>" data-piece-regular-price="<?php echo $this->escape((string) $item->sale_price); ?>" data-piece-nem="<?php echo $this->escape((string) $item->nem); ?>" data-piece-shots="<?php echo $this->escape((string) $item->shot_count); ?>"<?php if ($item->package['valid']) : ?> data-package-name="<?php echo $this->escape((string) $item->product_name . ' ' . $item->package['unit_type']); ?>" data-package-price="<?php echo $this->escape((string) $item->package['price']); ?>" data-package-regular-price="<?php echo $this->escape((string) $item->package['regular_price']); ?>" data-package-quantity="<?php echo (int) $item->package['unit_quantity']; ?>"<?php endif; ?>>
    <div class="fdshop-product__overview">
        <section class="fdshop-product__gallery" aria-label="Produktbilder">
            <div class="fdshop-product__main-image fdshop-product-visual--<?php echo $this->escape($item->visual_state); ?> is-primary" data-fdshop-main-stage data-visual-state="<?php echo $this->escape($item->visual_state); ?>">
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
                        <button type="button" class="fdshop-product__thumbnail<?php echo $index === 0 ? ' is-active' : ''; ?>" data-fdshop-thumbnail="<?php echo $this->escape($image); ?>" data-fdshop-primary="<?php echo $index === 0 ? '1' : '0'; ?>" aria-label="Produktbild <?php echo $index + 1; ?> anzeigen" aria-pressed="<?php echo $index === 0 ? 'true' : 'false'; ?>"><img src="<?php echo $this->escape($image); ?>" alt="" width="90" height="90" loading="lazy"></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <section class="fdshop-product__details">
            <div class="fdshop-product__heading">
                <h1 data-fdshop-package-name><?php echo $this->escape((string) $item->product_name); ?></h1>
                <?php if ($item->manufacturer_url !== '') : ?><p class="fdshop-product__manufacturer"><a href="<?php echo $this->escape($item->manufacturer_url); ?>"><?php echo $this->escape((string) $item->manufacturer_name); ?></a></p><?php endif; ?>
            </div>
            <?php if (trim((string) $item->short_description) !== '') : ?><div class="fdshop-product__short-description"><?php echo nl2br($this->escape((string) $item->short_description)); ?></div><?php endif; ?>
            <dl class="fdshop-product__facts" aria-label="Technische Produktdaten">
                <?php foreach ($factValues as $label => $value) : ?><div class="fdshop-product__fact" title="<?php echo $this->escape($label); ?>"><dt class="visually-hidden"><?php echo $this->escape($label); ?></dt><dd aria-label="<?php echo $this->escape($label . ': ' . $value); ?>"><img src="<?php echo $this->escape(\Joomla\CMS\Uri\Uri::root(true) . '/media/com_fdshop/images/product-facts/' . $factIcons[$label]); ?>" alt="" aria-hidden="true" width="42" height="42"><span<?php echo $label === 'NEM' ? ' data-fdshop-package-fact="nem"' : ($label === 'Schusszahl' ? ' data-fdshop-package-fact="shots"' : ''); ?>><?php echo $this->escape($value); ?></span></dd></div><?php endforeach; ?>
            </dl>
            <?php if ($item->media['video'] !== null) : ?>
                <div class="fdshop-product__video" data-fdshop-product-video><button type="button" class="fdshop-product__video-play" data-fdshop-video-inline="<?php echo $this->escape($item->media['video']); ?>" data-product-name="<?php echo $this->escape((string) $item->product_name); ?>" aria-label="Produktvideo zu <?php echo $this->escape((string) $item->product_name); ?> abspielen"><img src="https://i.ytimg.com/vi/<?php echo $this->escape($item->media['video_id']); ?>/hqdefault.jpg" alt="Video-Vorschaubild zu <?php echo $this->escape((string) $item->product_name); ?>" width="480" height="360" loading="lazy"><span class="fdshop-product__play-icon" aria-hidden="true"><i class="fa-solid fa-play"></i></span></button></div>
                <?php if (count($item->media['videos']) > 1) : ?><div class="fdshop-product__video-thumbnails" aria-label="Weitere Produktvideos">
                    <?php foreach (array_slice($item->media['videos'], 1) as $index => $video) : ?><button type="button" class="fdshop-product__video-thumbnail" data-fdshop-detail-video="<?php echo $this->escape($video['embed_url']); ?>" data-product-name="<?php echo $this->escape((string) $item->product_name); ?>" aria-label="Produktvideo <?php echo $index + 2; ?> zu <?php echo $this->escape((string) $item->product_name); ?> abspielen"><img src="https://i.ytimg.com/vi/<?php echo $this->escape($video['id']); ?>/mqdefault.jpg" alt="" width="120" height="90" loading="lazy"><span class="fdshop-product__play-icon" aria-hidden="true"><i class="fa-solid fa-play"></i></span></button><?php endforeach; ?>
                </div><?php endif; ?>
            <?php endif; ?>
            <div class="fdshop-product__purchase-zone" aria-label="Preis und Verfügbarkeit">
                <div class="fdshop-product__stock-copy"><strong>LAGERBESTAND:</strong><span><?php echo $this->escape($item->physical_stock_text); ?></span></div>
                <p class="fdshop-stock <?php echo $this->escape($item->stock_class); ?>"><strong><?php echo $this->escape((string) $item->in_stock); ?></strong></p>
                <?php if ($item->package['valid']) : ?><div class="fdshop-product__package"><label for="fdshop-unit-variant"><strong>als <?php echo $this->escape((string) $item->package['unit_type']); ?> bestellen <span title="Eine Verpackung enthält <?php echo (int) $item->package['unit_quantity']; ?> Stück">ⓘ</span></strong></label><select id="fdshop-unit-variant" class="form-select" data-fdshop-package-select><option value="piece"><?php echo $this->escape((string) $item->product_name); ?></option><option value="package"><?php echo $this->escape((string) $item->product_name . ' ' . $item->package['unit_type'] . ($item->package['unit_discount_type'] === 'percent' ? ' (-' . rtrim(rtrim(number_format((float) $item->package['unit_discount_value'], 2, ',', '.'), '0'), ',') . '%)' : '')); ?></option></select><small>Hier auswählen, wenn ihr eine Verpackungseinheit wollt.</small></div><?php endif; ?>
                <div class="fdshop-product__price" data-effective-price="<?php echo $this->escape((string) $item->current_price); ?>"><?php if ($item->has_discount || $item->package['valid']) : ?><span class="fdshop-product__regular-price" data-fdshop-package-regular<?php echo $item->has_discount ? '' : ' hidden'; ?>><?php echo $this->escape($item->regular_price_formatted); ?></span><?php endif; ?><strong data-fdshop-package-price><?php echo $this->escape($item->price_formatted); ?></strong><small>inkl. MwSt.</small></div>
                <?php if ($this->purchaseEnabled) : ?><?php echo LayoutHelper::render('purchase.action', PurchaseHelper::data($item, 'piece'), JPATH_COMPONENT_SITE . '/layouts'); ?><?php endif; ?>
                <?php if (!empty($item->bundles)) : ?>
                    <div class="fdshop-bundle-entry">
                        <?php if (count($item->bundles) === 1) : ?>
                            <button type="button" class="btn btn-outline-primary" data-fdshop-bundle-open="<?php echo (int) $item->bundles[0]->id; ?>">Bundle zusammenstellen</button>
                        <?php else : ?>
                            <label for="fdshop-bundle-choice">Bundle auswählen</label>
                            <select id="fdshop-bundle-choice" class="form-select" data-fdshop-bundle-choice><option value="">Bitte wählen</option><?php foreach ($item->bundles as $bundle) : ?><option value="<?php echo (int) $bundle->id; ?>"><?php echo $this->escape((string) $bundle->bundle_name); ?></option><?php endforeach; ?></select>
                            <button type="button" class="btn btn-outline-primary" data-fdshop-bundle-choice-open>Bundle zusammenstellen</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
    <?php if (trim((string) $item->description) !== '') : ?><section class="fdshop-product__description" aria-labelledby="fdshop-product-description-heading"><h2 id="fdshop-product-description-heading">Produktbeschreibung</h2><div><?php echo \Joomla\CMS\HTML\HTMLHelper::_('content.prepare', (string) $item->description); ?></div></section><?php endif; ?>
    <dialog class="fdshop-video" data-fdshop-detail-video-dialog aria-labelledby="fdshop-detail-video-title"><div class="fdshop-video__header"><h2 id="fdshop-detail-video-title" data-fdshop-detail-video-title>Produktvideo</h2><button type="button" class="fdshop-video__close" data-fdshop-detail-video-close aria-label="Video schließen">×</button></div><div class="fdshop-video__content" data-fdshop-detail-video-content></div></dialog>
    <?php if ($this->purchaseEnabled) : ?><?php echo LayoutHelper::render('purchase.modal', [], JPATH_COMPONENT_SITE . '/layouts'); ?><?php endif; ?>
    <?php if (!empty($item->bundles)) : ?>
        <dialog class="fdshop-bundle-dialog" data-fdshop-bundle-dialog data-builder-url="<?php echo $this->escape(Route::_('index.php?option=com_fdshop&task=bundle.builder&format=json', false)); ?>" data-calculate-url="<?php echo $this->escape(Route::_('index.php?option=com_fdshop&task=bundle.calculate&format=json', false)); ?>" data-save-url="<?php echo $this->escape(Route::_('index.php?option=com_fdshop&task=bundle.save&format=json', false)); ?>" data-delete-url="<?php echo $this->escape(Route::_('index.php?option=com_fdshop&task=bundle.deleteSaved&format=json', false)); ?>" data-cart-url="<?php echo $this->escape(Route::_('index.php?option=com_fdshop&task=bundle.addToCart&format=json', false)); ?>">
            <div class="fdshop-bundle-dialog__content"><button type="button" class="fdshop-bundle-dialog__close" data-fdshop-bundle-close aria-label="Bundle-Konfigurator schließen">×</button><div data-fdshop-bundle-content></div></div>
        </dialog>
        <form hidden data-fdshop-bundle-token><?php echo HTMLHelper::_('form.token'); ?></form>
    <?php endif; ?>
</main>
