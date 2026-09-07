<?php
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\PluginHelper;

$moduleId = (int) $module->id;
$panelId  = 'cart-panel-' . $moduleId;
$labelId  = 'cartpanelLabel-' . $moduleId;

$showSubtotal = ($show_price && !empty($currencyDisplay->_priceConfig['salesPrice'][0]));
?>
<div class="vmCartModule <?php echo $params->get('moduleclass_sfx'); ?>" id="vmCartModule<?php echo $moduleId; ?>" data-module-id="<?php echo $moduleId; ?>">

	<div class="button-wrapper">
	  <button class="btn btn-primary"
			  type="button"
			  data-bs-toggle="offcanvas"
			  data-bs-target="#<?php echo $panelId; ?>"
			  aria-controls="<?php echo $panelId; ?>"
			  aria-label="Warenkorb anzeigen">
		<i class="fa fa-shopping-basket" aria-hidden="true"></i>

		<span class="products-number">
		  <?php echo ($data->totalProduct > 0) ? $data->totalProductTxt : '0'; ?>
		</span>
	  </button>
	</div>

  <div class="offcanvas offcanvas-end fd-cartcanvas"
       tabindex="-1"
       id="<?php echo $panelId; ?>"
       aria-labelledby="<?php echo $labelId; ?>">

    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="<?php echo $labelId; ?>">
        <?php echo vmText::_('COM_VIRTUEMART_CART_SHOW'); ?>
      </h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
              aria-label="<?php echo vmText::_('JLIB_HTML_BEHAVIOR_CLOSE'); ?>"></button>
    </div>

    <div class="offcanvas-body">

      <?php if ($show_product_list) : ?>

        <!-- VM Ajax Template: keep it -->
        <div class="hiddencontainer fd-cart-hidden" aria-hidden="true">
          <div class="vmcontainer">
            <div class="product_row fd-cart-row">
              <div class="fd-cart-left">
                <span class="quantity"></span><span class="fd-cart-times">×</span><span class="product_name"></span>
              </div>

              <?php if ($showSubtotal) : ?>
                <div class="subtotal_with_tax fd-cart-right"></div>
              <?php endif; ?>

              <div class="customProductData fd-cart-custom"></div>
            </div>
          </div>
        </div>

		<?php //var_dump $data; 
		//var_dump($data);
		?>
        <div class="vm_cart_products">
          <div class="vmcontainer">
			  <?php 
             foreach ($data->products as $product) : ?>
              <div class="product_row fd-cart-row">
                <div class="fd-cart-left">
                  <span class="quantity"><?php echo (int) $product['quantity']; ?></span>
                  <span class="fd-cart-times">×</span>
                  <span class="product_name"><?php echo $product['product_name']; ?></span>
                </div>

                <?php if ($showSubtotal) : ?>
                  <div class="subtotal_with_tax fd-cart-right"><?php echo $product['subtotal_with_tax']; ?></div>
                <?php endif; ?>

                <?php if (!empty($product['customProductData'])) : ?>
                  <div class="customProductData fd-cart-custom"><?php echo $product['customProductData']; ?></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

      <?php endif; ?>

	<div class="fd-cart-summary">
		
		<!--<div class="fd-cart-summary-coupon">--> 
		<?php 
		// Frühbucherrabatt nur anzeigen, wenn Plugin aktiv ist
		$fdAutoCouponActive = PluginHelper::isEnabled('system', 'fdautocoupon');
		
		$discountAmount = 0.0;

		// Falls nicht, abbrechen (keine Anzeige, kein Fehler).
		if ($fdAutoCouponActive && isset($cart) && !empty($cart->cartPrices['billTotal'])) {

			$finalTotal = (float) $cart->cartPrices['billTotal'];

			if ($finalTotal > 0) {
				$originalTotal = $finalTotal / 0.9;
				$diff = $originalTotal - $finalTotal;

				$discountCents  = (int) floor(($diff * 100) + 1e-6);
				$discountAmount = $discountCents / 100;
			}
		}
		?>
		<?php if ($fdAutoCouponActive): ?>		
			<div class="fd-cart-summary-top" <?php echo ($discountAmount > 0 ? '' : 'hidden'); ?>>
			  <div class="fd-cart-discount-label"><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_FRUERABATT'); ?></div>
				<div class="fd-cart-discount-value">
				<?php
				  // Nur ausgeben, wenn wirklich > 0 – sonst bleibt’s leer
				  echo ($discountAmount > 0)
					? $currencyDisplay->createPriceDiv('fdDiscount', '', -$discountAmount, false)
					: '';
				?>
				</div>
			</div>
		<?php endif; ?>		
 
		<!--</div>-->
		  
		  
        <div class="fd-cart-summary-top">
			<div class="total_products"><?php echo $data->totalProductTxt; ?></div>

			<div class="total">
				<?php if ($data->totalProduct and $showSubtotal and $currencyDisplay->_priceConfig['salesPrice'][0]): ?>
				<?php echo $data->billTotal; ?>
				<?php endif; ?>
			</div>
        </div>

        <div class="show_cart">
          <?php if ($data->totalProduct) : ?>
            <a class="btn btn-primary w-100" href="<?php echo $data->cart_show_link; ?>" rel="nofollow">
              <?php echo $data->linkName; ?>
            </a>
          <?php endif; ?>
        </div>

        <?php
        $view = vRequest::getCmd('view');
        if ($view !== 'cart' && $view !== 'user') : ?>
          <div class="payments-signin-button"></div>
        <?php endif; ?>
      </div>

      <noscript><?php echo vmText::_('MOD_VIRTUEMART_CART_AJAX_CART_PLZ_JAVASCRIPT'); ?></noscript>
    </div>
  </div>
</div>
