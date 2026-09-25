<?php

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Helper\PurchaseHelper;
use FDShop\Component\FDShop\Site\Helper\RouteHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Uri\Uri;

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$moduleId = 'fdshop-products-' . (int) $module->id;
$desktopItems = min(6, max(3, (int) $params->get('desktop_items', 5)));
$carousel = (int) $params->get('carousel', 1) === 1;
$showRibbons = (int) $params->get('show_ribbons', 1) === 1;
$showTechnical = (int) $params->get('show_technical', 1) === 1;
$showPrice = (int) $params->get('show_price', 1) === 1;
$showCart = (int) $params->get('show_cart', 1) === 1 && $purchaseEnabled;
$categoryUrl = RouteHelper::getCategoryRoute((int) $category->id);
$factsRoot = rtrim(Uri::root(true), '/') . '/media/com_fdshop/images/product-facts/';
?>
<section id="<?php echo $moduleId; ?>" class="fdshop-products-module<?php echo $carousel ? ' is-carousel' : ' is-grid'; ?>" data-fdshop-products-module data-cache-mode="<?php echo (int) $params->get('cache', 1); ?>" data-query-count="<?php echo (int) $data['query_count']; ?>" data-request-cache-hit="<?php echo !empty($data['request_cache_hit']) ? '1' : '0'; ?>" data-render-ms="<?php echo number_format((float) $data['render_ms'], 3, '.', ''); ?>" style="--fdshop-module-columns:<?php echo $desktopItems; ?>">
  <header class="fdshop-products-module__header">
    <?php if ((int) $module->showtitle === 0) : ?><h2><?php echo $escape($module->title); ?></h2><?php endif; ?>
    <?php if ((int) $params->get('show_category_link', 1) === 1) : ?><a class="btn btn-primary btn-sm" href="<?php echo $escape($categoryUrl); ?>"><?php echo $escape($params->get('category_link_text', 'Zur Kategorie')); ?></a><?php endif; ?>
  </header>
  <div class="fdshop-products-module__viewport" data-products-viewport tabindex="0" aria-label="Produkte aus <?php echo $escape($category->category_name); ?>">
    <div class="fdshop-products-module__track">
    <?php foreach ($items as $item) : ?>
      <article class="fdshop-card fdshop-products-module__card" data-product-id="<?php echo (int) $item->id; ?>" data-visual-state="<?php echo $escape($item->visual_state); ?>">
        <div class="fdshop-card__visual fdshop-product-visual--<?php echo $escape($item->visual_state); ?>">
          <div class="fdshop-card__media">
            <a class="fdshop-card__image-link" href="<?php echo $escape($item->detail_url); ?>" aria-label="Details zu <?php echo $escape($item->product_name); ?>">
              <?php if ($item->image_is_placeholder) : ?>
                <img class="fdshop-card__placeholder" src="<?php echo $escape($item->image_url); ?>" alt="<?php echo $escape($item->product_name); ?>" loading="lazy" width="400" height="400">
              <?php else : ?>
                <picture><source media="(max-width: 520px)" srcset="<?php echo $escape($item->image_mobile_url); ?>"><source media="(min-width: 521px)" srcset="<?php echo $escape($item->image_small_url); ?>"><img class="fdshop-card__product-image" src="<?php echo $escape($item->image_url); ?>" alt="<?php echo $escape($item->product_name); ?>" loading="lazy" width="400" height="400"></picture>
              <?php endif; ?>
            </a>
            <?php if ($showRibbons) : ?><div class="fdshop-card__ribbons" aria-label="Produktkennzeichnungen">
              <?php if ($item->visual_state === 'action') : ?><span class="fdshop-ribbon fdshop-ribbon--action">% Angebot</span><?php endif; ?>
              <?php if ((int) $item->ribbon_new === 1) : ?><span class="fdshop-ribbon fdshop-ribbon--new">Neu</span><?php endif; ?>
              <?php if ((int) $item->ribbon_hot === 1) : ?><span class="fdshop-ribbon fdshop-ribbon--hot">Hot</span><?php endif; ?>
              <?php if ((int) $item->ribbon_bundle === 1) : ?><span class="fdshop-ribbon fdshop-ribbon--bundle">Bundle</span><?php endif; ?>
              <?php if (in_array((string) $item->unit_type, ['Display', 'Schinken', 'VE'], true)) : ?><span class="fdshop-ribbon fdshop-ribbon--package"><?php echo $escape(strtoupper((string) $item->unit_type)); ?></span><?php endif; ?>
            </div><?php endif; ?>
          </div>
          <div class="fdshop-card__body"><h3 class="fdshop-card__title"><a href="<?php echo $escape($item->detail_url); ?>"><?php echo $escape($item->product_name); ?></a></h3></div>
        </div>
        <div class="fdshop-card__info">
          <?php if ($showTechnical) : ?><dl class="fdshop-card__facts" aria-label="Technische Produktdaten"><?php foreach ($item->card_facts as $fact) : ?><div class="fdshop-card__fact" title="<?php echo $escape($fact['label']); ?>"><dt class="visually-hidden"><?php echo $escape($fact['label']); ?></dt><dd aria-label="<?php echo $escape($fact['label'] . ': ' . $fact['value']); ?>"><img src="<?php echo $escape($factsRoot . $fact['icon']); ?>" alt="" aria-hidden="true" width="28" height="28" loading="lazy"><span><?php echo $escape($fact['value']); ?></span></dd></div><?php endforeach; ?></dl><?php endif; ?>
          <?php if ($showPrice || $showCart) : ?><div class="fdshop-card__commerce">
            <?php if ($showPrice) : ?><div class="fdshop-card__price" data-effective-price="<?php echo $escape($item->current_price); ?>"><?php if ($item->has_discount) : ?><span class="fdshop-card__regular-price"><?php echo $escape($item->regular_price_formatted); ?></span><?php endif; ?><strong><?php echo $escape($item->price_formatted); ?></strong><small>inkl. MwSt.</small></div><?php endif; ?>
            <?php if ($showCart) : ?><?php echo LayoutHelper::render('purchase.action', PurchaseHelper::data($item, 'piece', 'module-' . (int) $module->id), JPATH_ROOT . '/components/com_fdshop/layouts'); ?><?php endif; ?>
          </div><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
    </div>
  </div>
  <?php if ($carousel && count($items) > $desktopItems) : ?><nav class="fdshop-products-module__navigation" aria-label="Karussellnavigation"><button type="button" class="btn btn-outline-secondary" data-products-prev aria-label="Vorherige Produkte"><span aria-hidden="true">‹</span></button><button type="button" class="btn btn-outline-secondary" data-products-next aria-label="Nächste Produkte"><span aria-hidden="true">›</span></button></nav><?php endif; ?>
</section>
<?php
static $purchaseModalRendered = false;
if ($showCart && !$purchaseModalRendered) {
    echo LayoutHelper::render('purchase.modal', [], JPATH_ROOT . '/components/com_fdshop/layouts');
    $purchaseModalRendered = true;
}
