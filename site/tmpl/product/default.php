<?php

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Helper\PurchaseHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

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
$packageType = ucfirst(trim((string) ($item->package['unit_type'] ?? '')));
$packageHelper = strtolower($packageType) === 'schinken'
    ? 'Hier auswählen, wenn ihr einen Schinken wollt.'
    : 'Hier auswählen, wenn ihr ein Display wollt.';
$packageDiscount = ($item->package['unit_discount_type'] ?? '') === 'percent'
    ? ' (-' . rtrim(rtrim(number_format((float) $item->package['unit_discount_value'], 2, ',', '.'), '0'), ',') . '%)'
    : '';
?>
<main class="fdshop-product" data-product-id="<?php echo (int) $item->id; ?>" data-category-id="<?php echo $this->categoryId; ?>" data-fdshop-package-root data-piece-name="<?php echo $this->escape((string) $item->product_name); ?>" data-piece-price="<?php echo $this->escape((string) $item->current_price); ?>" data-piece-regular-price="<?php echo $this->escape((string) $item->sale_price); ?>" data-piece-nem="<?php echo $this->escape((string) $item->nem); ?>" data-piece-shots="<?php echo $this->escape((string) $item->shot_count); ?>"<?php if ($item->package['valid']) : ?> data-package-name="<?php echo $this->escape((string) $item->product_name . ' ' . $item->package['unit_type']); ?>" data-package-price="<?php echo $this->escape((string) $item->package['price']); ?>" data-package-regular-price="<?php echo $this->escape((string) $item->package['regular_price']); ?>" data-package-quantity="<?php echo (int) $item->package['unit_quantity']; ?>"<?php endif; ?>>
    <div class="fdshop-product__overview">
        <section class="fdshop-product__gallery" aria-label="Produktbilder">
            <div class="fdshop-product__main-image fdshop-product-visual--<?php echo $this->escape($item->visual_state); ?> is-primary" data-fdshop-main-stage data-visual-state="<?php echo $this->escape($item->visual_state); ?>">
                <img src="<?php echo $this->escape($mainImage); ?>" alt="<?php echo $this->escape((string) $item->product_name); ?>" width="700" height="700" data-fdshop-main-image>
                <div class="fdshop-card__ribbons" aria-label="Produktkennzeichnungen">
                    <?php if ((int) $item->ribbon_new === 1) : ?><span class="fdshop-ribbon fdshop-ribbon--new">Neu</span><?php endif; ?>
                    <?php if ((int) $item->ribbon_hot === 1) : ?><span class="fdshop-ribbon fdshop-ribbon--hot">Hot</span><?php endif; ?>
                    <?php if ((int) $item->ribbon_bundle === 1) : ?><span class="fdshop-ribbon fdshop-ribbon--bundle">Bundle</span><?php endif; ?>
                    <?php if ((string) ($item->buyer_group_alias ?? '') === 'permit_holder') : ?><span class="fdshop-ribbon fdshop-ribbon--f3">F3</span><?php endif; ?>
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
                <div class="fdshop-product__action-zone">
                    <?php if ($item->package['valid']) : ?><div class="fdshop-product__action fdshop-product__package"><div class="fdshop-product__package-control"><i class="fa-solid fa-box-open" aria-hidden="true"></i><label class="visually-hidden" for="fdshop-unit-variant"><?php echo $this->escape($packageType); ?> auswählen</label><select id="fdshop-unit-variant" data-fdshop-package-select aria-label="<?php echo $this->escape($packageType); ?> auswählen"><option value="piece">Einzelpackung</option><option value="package"><?php echo $this->escape($packageType . $packageDiscount); ?></option></select></div><small><i class="fa-solid fa-circle-info" aria-hidden="true"></i> <?php echo $this->escape($packageHelper); ?></small></div><?php endif; ?>
                    <?php if (!empty($item->bundles)) : ?>
                        <div class="fdshop-product__action fdshop-product__bundle-action">
                            <?php if (count($item->bundles) === 1) : ?>
                                <button type="button" class="btn btn-primary fdshop-product__action-button" data-fdshop-bundle-open="<?php echo (int) $item->bundles[0]->id; ?>"><i class="fa-solid fa-cubes-stacked" aria-hidden="true"></i><span>Bundle erstellen</span></button>
                            <?php else : ?>
                                <button type="button" class="btn btn-primary fdshop-product__action-button" data-fdshop-bundle-picker-open><i class="fa-solid fa-cubes-stacked" aria-hidden="true"></i><span>Bundle erstellen</span></button>
                            <?php endif; ?>
                            <small><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Hier auswählen, wenn ihr ein Bundle wollt.</small>
                        </div>
                    <?php endif; ?>
                    <div class="fdshop-product__action fdshop-product__question-action"><button type="button" class="btn btn-primary fdshop-product__action-button" data-product-question-open><i class="fa-solid fa-comment-dots" aria-hidden="true"></i><span>Frage stellen</span></button></div>
                </div>
                <div class="fdshop-product__commerce">
                <div class="fdshop-product__price" data-effective-price="<?php echo $this->escape((string) $item->current_price); ?>"><?php if ($item->has_discount || $item->package['valid']) : ?><span class="fdshop-product__regular-price" data-fdshop-package-regular<?php echo $item->has_discount ? '' : ' hidden'; ?>><?php echo $this->escape($item->regular_price_formatted); ?></span><?php endif; ?><strong data-fdshop-package-price><?php echo $this->escape($item->price_formatted); ?></strong><small>inkl. MwSt.</small></div>
                <?php if ($this->purchaseEnabled) : ?><?php echo LayoutHelper::render('purchase.action', PurchaseHelper::data($item, 'piece'), JPATH_COMPONENT_SITE . '/layouts'); ?><?php endif; ?>
                </div>
            </div>
        </section>
    </div>
    <?php if (trim((string) $item->description) !== '') : ?><section class="fdshop-product__description" aria-labelledby="fdshop-product-description-heading"><h2 id="fdshop-product-description-heading">Produktbeschreibung</h2><div><?php echo nl2br($this->escape((string) $item->description)); ?></div></section><?php endif; ?>
    <dialog class="fdshop-video" data-fdshop-detail-video-dialog aria-labelledby="fdshop-detail-video-title"><div class="fdshop-video__header"><h2 id="fdshop-detail-video-title" data-fdshop-detail-video-title>Produktvideo</h2><button type="button" class="fdshop-video__close" data-fdshop-detail-video-close aria-label="Video schließen">×</button></div><div class="fdshop-video__content" data-fdshop-detail-video-content></div></dialog>
    <dialog class="fdshop-question-dialog" data-product-question-dialog aria-labelledby="fdshop-question-title"><form method="post" action="<?php echo Route::_('index.php?option=com_fdshop&task=interaction.question'); ?>"><button type="button" class="fdshop-question-dialog__close" data-product-question-close aria-label="<?php echo Text::_('COM_FDSHOP_WATCH_CLOSE'); ?>">×</button><h2 id="fdshop-question-title"><?php echo Text::sprintf('COM_FDSHOP_QUESTION_TITLE',$this->escape((string)$item->product_name)); ?></h2><p><?php echo Text::_('COM_FDSHOP_QUESTION_NOTE'); ?></p><?php echo $this->questionForm->renderField('name'); ?><?php echo $this->questionForm->renderField('email'); ?><?php echo $this->questionForm->renderField('question'); ?><div class="fdshop-honeypot" aria-hidden="true"><?php echo $this->questionForm->renderField('website'); ?></div><?php echo $this->questionForm->renderField('captcha'); ?><input type="hidden" name="product_id" value="<?php echo (int)$item->id; ?>"><input type="hidden" name="catid" value="<?php echo (int)Factory::getApplication()->getInput()->getInt('catid'); ?>"><button type="submit" class="btn btn-primary"><?php echo Text::_('COM_FDSHOP_QUESTION_SEND'); ?></button><?php echo HTMLHelper::_('form.token'); ?></form></dialog>
    <?php if ($this->purchaseEnabled && PurchaseHelper::claimModal()) : ?><?php echo LayoutHelper::render('purchase.modal', [], JPATH_COMPONENT_SITE . '/layouts'); ?><?php endif; ?>
    <?php if (!empty($item->bundles)) : ?>
        <?php if (count($item->bundles) > 1) : ?><dialog class="fdshop-bundle-picker" data-fdshop-bundle-picker aria-labelledby="fdshop-bundle-picker-title"><div class="fdshop-bundle-picker__content"><button type="button" class="fdshop-bundle-picker__close" data-fdshop-bundle-picker-close aria-label="Bundle-Auswahl schließen">×</button><h2 id="fdshop-bundle-picker-title">Bundle auswählen</h2><p>Dieses Produkt ist für mehrere Bundles verfügbar. Bitte wählt aus, welches Bundle ihr zusammenstellen möchtet.</p><div class="fdshop-bundle-picker__options"><?php foreach ($item->bundles as $bundle) : ?><button type="button" class="btn btn-primary" data-fdshop-bundle-picker-choice="<?php echo (int) $bundle->id; ?>"><?php echo $this->escape((string) $bundle->bundle_name); ?></button><?php endforeach; ?></div></div></dialog><?php endif; ?>
        <dialog class="fdshop-bundle-dialog" data-fdshop-bundle-dialog data-builder-url="<?php echo $this->escape(Route::_('index.php?option=com_fdshop&task=bundle.builder&format=json', false)); ?>" data-calculate-url="<?php echo $this->escape(Route::_('index.php?option=com_fdshop&task=bundle.calculate&format=json', false)); ?>" data-save-url="<?php echo $this->escape(Route::_('index.php?option=com_fdshop&task=bundle.save&format=json', false)); ?>" data-delete-url="<?php echo $this->escape(Route::_('index.php?option=com_fdshop&task=bundle.deleteSaved&format=json', false)); ?>" data-cart-url="<?php echo $this->escape(Route::_('index.php?option=com_fdshop&task=bundle.addToCart&format=json', false)); ?>">
            <div class="fdshop-bundle-dialog__content"><button type="button" class="fdshop-bundle-dialog__close" data-fdshop-bundle-close aria-label="Bundle-Konfigurator schließen">×</button><div data-fdshop-bundle-content></div></div>
        </dialog>
        <form hidden data-fdshop-bundle-token><?php echo HTMLHelper::_('form.token'); ?></form>
    <?php endif; ?>
</main>
