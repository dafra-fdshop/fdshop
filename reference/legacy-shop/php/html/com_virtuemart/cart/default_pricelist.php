<?php
/**
 * Layout for the shopping cart
 *
 * @package    VirtueMart
 * @subpackage Cart
 * @author Max Milbers
 *
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2016 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @version $Id: cart.php 2551 2010-09-30 18:52:40Z milbo $
 */

// Check to ensure this file is included in Joomla!
defined ('_JEXEC') or die('Restricted access');

use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

$fdAutoCouponEnabled = false;
$fdAutoCouponLabel   = '';

if (PluginHelper::isEnabled('system', 'fdautocoupon')) {
    $p = PluginHelper::getPlugin('system', 'fdautocoupon');
    $r = new Registry($p->params);

    $fdAutoCouponEnabled = (bool) $r->get('enabled', 0);
    $fdAutoCouponLabel   = trim((string) $r->get('display_label', ''));
}

?>

<div class="fd-cart-grid">

<!--Beginn Schritt 2 (kauf prüfen)-->

<div class="table-step">
<h3><?php echo vmText::_ ('COM_VIRTUEMART_CART_STEP_2') ?></h3>
<table
	class="cart-summary"
	cellspacing="0"
	cellpadding="0"
	border="0"
	width="100%">
<tr>
	<th style="min-width:80px;width:10%; text-align: left;" ><?php echo vmText::_ ('COM_VIRTUEMART_CART_SKU') ?></th>
	<th align="left" ><?php echo vmText::_ ('COM_VIRTUEMART_CART_NAME') ?></th>
	<th	style="min-width:85px;width:11%;" ><?php echo vmText::_ ('COM_VIRTUEMART_CART_PRICE') ?></th>
	<th	style="min-width:160px;width:20%;" ><?php echo vmText::_ ('COM_VIRTUEMART_CART_QUANTITY') ?></th>
	<?php if (VmConfig::get ('show_tax')) {
		$tax = vmText::_ ('COM_VIRTUEMART_CART_SUBTOTAL_TAX_AMOUNT');
		if(!empty($this->cart->cartData['VatTax'])){
			if(count($this->cart->cartData['VatTax']) < 2) {
				reset($this->cart->cartData['VatTax']);
				$taxd = current($this->cart->cartData['VatTax']);
				$tax = shopFunctionsF::getTaxNameWithValue(vmText::_($taxd['calc_name']),$taxd['calc_value']);
			}
		}
		?>
	
	<?php } ?>
	
	<th style="min-width:100px;width:12%; text-align: right;" ><?php echo vmText::_ ('COM_VIRTUEMART_CART_TOTAL') ?></th>
</tr>

<?php
$i = 1;

foreach ($this->cart->products as $pkey => $prow) {
	$prow->prices = array_merge($prow->prices,$this->cart->cartPrices[$pkey]);
?>

<tr style="vertical-align: top" class="sectiontableentry<?php echo $i; if(!empty($prow->class)) echo ' '.$prow->class ?>">
	<td align="left" ><?php  echo $prow->product_sku ?></td>
	<td align="left" >
		<input type="hidden" name="cartpos[]" value="<?php echo $pkey ?>">
		<?php if ($prow->virtuemart_media_id) { ?>
		<span class="cart-images">
			<?php
			if (!empty($prow->images[0])) {
				echo $prow->images[0]->displayMediaThumb ('', FALSE);
			} ?>
		</span>
		<?php } ?>
		<?php echo JHtml::link ($prow->url, $prow->product_name);
			echo $this->customfieldsModel->CustomsFieldCartDisplay ($prow); 			
			?>
	</td>
	<td align="right">
		<?php
	
		if (VmConfig::get ('checkout_show_origprice', 1) && $prow->prices['discountedPriceWithoutTax'] != $prow->prices['priceWithoutTax']) {
			echo '<div class="PricebasePriceWithTax"><span class="PricesalesPrice">' . $this->currencyDisplay->createPriceDiv ('basePriceWithTax', '', $prow->prices, TRUE, FALSE) . '</span></div>';
		}
		if ($prow->prices['discountedPriceWithoutTax']) {
			echo $this->currencyDisplay->createPriceDiv ('salesPrice', '', $prow->prices, FALSE, FALSE, 1.0, false, true);
		} else {
			echo $this->currencyDisplay->createPriceDiv ('basePriceVariant', '', $prow->prices, FALSE, FALSE, 1.0, false, true);
		} ?>
	</td>
	<td align="left" ><?php
		if ($prow->step_order_level)
			$step=$prow->step_order_level;
		else
			$step=1;
		if($step==0)
			$step=1;
		?>
		<input type="text"
			onblur="Virtuemart.checkQuantity(this,<?php echo $step?>,'<?php echo vmText::_ ('COM_VIRTUEMART_WRONG_AMOUNT_ADDED',true)?>');"
			onclick="Virtuemart.checkQuantity(this,<?php echo $step?>,'<?php echo vmText::_ ('COM_VIRTUEMART_WRONG_AMOUNT_ADDED',true)?>');"
			onchange="Virtuemart.checkQuantity(this,<?php echo $step?>,'<?php echo vmText::_ ('COM_VIRTUEMART_WRONG_AMOUNT_ADDED',true)?>');"
			onsubmit="Virtuemart.checkQuantity(this,<?php echo $step?>,'<?php echo vmText::_ ('COM_VIRTUEMART_WRONG_AMOUNT_ADDED',true)?>');"
			title="<?php echo  vmText::_('COM_VIRTUEMART_CART_UPDATE') ?>" class="quantity-input js-recalculate" size="3" maxlength="4" name="quantity[<?php echo $pkey; ?>]" value="<?php echo $prow->quantity ?>" />
        <!--span class="quantity-controls js-recalculate">
				<input type="button" class="quantity-controls quantity-plus"/>
				<input type="button" class="quantity-controls quantity-minus"/>
        </span-->
		<button type="submit" class="vmicon vm2-add_quantity_cart" name="updatecart.<?php echo $pkey ?>" title="<?php echo  vmText::_ ('COM_VIRTUEMART_CART_UPDATE') ?>" data-dynamic-update="1" ></button>
		<button type="submit" class="vmicon vm2-remove_from_cart" name="delete.<?php echo $pkey ?>" title="<?php echo vmText::_ ('COM_VIRTUEMART_CART_DELETE') ?>" ></button>
	</td>
	
	<td colspan="1" align="right">
		<?php
		if (VmConfig::get ('checkout_show_origprice', 1) && !empty($prow->prices['basePriceWithTax']) && $prow->prices['basePriceWithTax'] != $prow->prices['salesPrice']) {
			echo '<div class="PricebasePriceWithTax"><span class="PricesalesPrice">' . $this->currencyDisplay->createPriceDiv ('basePriceWithTax', '', $prow->prices, TRUE, FALSE, $prow->quantity) . '</span></div><br />';
		}
		elseif (VmConfig::get ('checkout_show_origprice', 1) && empty($prow->prices['basePriceWithTax']) && !empty($prow->prices['basePriceVariant']) && $prow->prices['basePriceVariant'] != $prow->prices['salesPrice']) {
			echo '<span class="line-through">' . $this->currencyDisplay->createPriceDiv ('basePriceVariant', '', $prow->prices, TRUE, FALSE, $prow->quantity) . '</span><br />';
		}
		echo $this->currencyDisplay->createPriceDiv ('salesPrice', '', $prow->prices, FALSE, FALSE, $prow->quantity) ?></td>
</tr>



	<?php
	$i = ($i==1) ? 2 : 1;
} ?>
<!--Begin of SubTotal, Tax, Shipment, Coupon Discount and Total listing -->
<?php if (VmConfig::get ('show_tax')) {
	$colspan = 3;
} else {
	$colspan = 2;
} ?>

</table>
</div>
 

<!--Beginn Schritt 3 (Versandart)-->
<div class="fd-checkout-grid">
<div class="table-step">
<h3><?php echo vmText::_ ('COM_VIRTUEMART_CART_STEP_3') ?></h3>
<table
	class="cart-summary"
	cellspacing="0"
	cellpadding="0"
	border="0"
	width="100%">



<tr class="sectiontableentry1">
	<td colspan="2" style="text-align: right; border-bottom: none; border-top: thin solid #B4B1B1;"><?php echo vmText::_ ('COM_VIRTUEMART_ORDER_PRINT_PRODUCT_PRICES_TOTAL'); ?></td>

	<td style="text-align: right; border-bottom: none; border-top: thin solid #B4B1B1;"><?php echo $this->currencyDisplay->createPriceDiv ('salesPrice', '', $this->cart->cartPrices, FALSE) ?></td>
</tr>

<?php
	
// Hier beginnt der neue Coupon-Teil für das Frühbucher PlugIn
if (VmConfig::get('coupons_enable')) : ?>
<tr class="sectiontableentry2">
  <td colspan="2" style="text-align: right;">

    <?php
    // Eingabefeld nur anzeigen, wenn Auto-Coupon NICHT aktiv ist
    if (
        !$fdAutoCouponEnabled
        && !empty($this->layoutName)
        && $this->layoutName == $this->cart->layout
    ) {
        echo $this->loadTemplate('coupon');
    }
    ?>

    <?php if (!empty($this->cart->cartData['couponCode'])) : ?>

     <?php
		// Optische Anzeige: bei Auto-Coupon lieber nur das Label anzeigen
		if ($fdAutoCouponEnabled && $fdAutoCouponLabel !== '') {
			echo $fdAutoCouponLabel;
		} else {
			echo $this->cart->cartData['couponCode'];

			echo $this->cart->cartData['couponDescr']
				? (' (' . $this->cart->cartData['couponDescr'] . ')')
				: '';
		}
	?>

  </td>

      <td style="text-align: right;">
		
		 <?php 	 
		 $couponRaw = (float) $this->cart->cartPrices['salesPriceCoupon'];

		// Für negative Zahlen korrekt abschneiden:
		if ($couponRaw < 0) {
			$couponCents = (int) ceil($couponRaw * 100);
		} else {
			$couponCents = (int) floor($couponRaw * 100);
		}

		$couponAmount = $couponCents / 100;
			 
		 echo $this->currencyDisplay->createPriceDiv('fdCoupon', '', $couponAmount, false);
		  ?>

      </td>

    <?php else : ?>
      &nbsp;</td>
      <td>&nbsp;</td>
    <?php endif; ?>
</tr>
<?php endif; ?>


<!--Ab hier kommen die Versand und Bezahlarten -->
<?php

foreach ($this->cart->cartData['DBTaxRulesBill'] as $rule) {
?>
<tr class="sectiontableentry<?php echo $i ?>">
	<td colspan="2" style="text-align: right;"><?php echo vmText::_($rule['calc_name']) ?> </td>
	<?php if (VmConfig::get ('show_tax')) { ?>
	<td style="text-align: right;"></td>
	<?php } ?>

	<td style="text-align: right;"><?php echo $this->currencyDisplay->createPriceDiv ($rule['virtuemart_calc_id'] . 'Diff', '', $this->cart->cartPrices[$rule['virtuemart_calc_id'] . 'Diff'], FALSE); ?>&nbsp;</td>
</tr>
	<?php
	if ($i) {
		$i = 1;
	} else {
		$i = 0;
	}
} ?>

<?php

foreach ($this->cart->cartData['taxRulesBill'] as $rule) {
	if($rule['calc_value_mathop']=='avalara') continue;
	?>
<tr class="sectiontableentry<?php echo $i ?>">
	<td colspan="2" style="text-align: right;"><?php echo vmText::_($rule['calc_name']) ?> </td>
	<?php if (VmConfig::get ('show_tax')) { ?>
	<td style="text-align: right;"><?php echo $this->currencyDisplay->createPriceDiv ($rule['virtuemart_calc_id'] . 'Diff', '', $this->cart->cartPrices[$rule['virtuemart_calc_id'] . 'Diff'], FALSE); ?>&nbsp;</td>
	<?php } ?>

	<td style="text-align: right;"><?php echo $this->currencyDisplay->createPriceDiv ($rule['virtuemart_calc_id'] . 'Diff', '', $this->cart->cartPrices[$rule['virtuemart_calc_id'] . 'Diff'], FALSE); ?>&nbsp;</td>
</tr>
	<?php
	if ($i) {
		$i = 1;
	} else {
		$i = 0;
	}
}

foreach ($this->cart->cartData['DATaxRulesBill'] as $rule) {
	?>
<tr class="sectiontableentry<?php echo $i ?>">
	<td colspan="2" style="text-align: right;"><?php echo vmText::_($rule['calc_name']) ?> </td>
	<?php if (VmConfig::get ('show_tax')) { ?>
	<td style="text-align: right;">&nbsp;</td>
	<?php } ?>
	<td style="text-align: right;"><?php echo $this->currencyDisplay->createPriceDiv ($rule['virtuemart_calc_id'] . 'Diff', '', $this->cart->cartPrices[$rule['virtuemart_calc_id'] . 'Diff'], FALSE); ?> </td>
	<td style="text-align: right;"><?php echo $this->currencyDisplay->createPriceDiv ($rule['virtuemart_calc_id'] . 'Diff', '', $this->cart->cartPrices[$rule['virtuemart_calc_id'] . 'Diff'], FALSE); ?> </td>
</tr>
	<?php
	if ($i) {
		$i = 1;
	} else {
		$i = 0;
	}
}

if (VmConfig::get('oncheckout_opc',true) or
	!VmConfig::get('oncheckout_show_steps',false) or
	(!VmConfig::get('oncheckout_opc',true) and VmConfig::get('oncheckout_show_steps',false) and
	!empty($this->cart->virtuemart_shipmentmethod_id) )
) { ?>

	
<tr class="sectiontableentry1" style="vertical-align:top;">
  <td colspan="2" style="align:left;vertical-align:top;">

    <div class="fd-shipment-head">
      <div class="fd-shipment-title">
        <?php echo '<h5>'.vmText::_('COM_VIRTUEMART_CART_SELECTED_SHIPMENT').'</h5>'; ?>
        <div class="fd-shipment-current">
          <?php echo $this->cart->cartData['shipmentName']; ?>
        </div>
      </div>

      <?php if (!empty($this->layoutName) && $this->layoutName == $this->cart->layout && VmConfig::get('oncheckout_opc', 0)) : ?>
        <!-- Toggle: Details -->
        <details class="fd-shipment-details" id="fdShipmentToggle">
          <summary class="fd-shipment-summary" role="button"><?php echo vmText::_('COM_VIRTUEMART_CART_BTN_SWITCH'); ?></summary>

          <div class="fd-shipment-body">
            <?php
              $previouslayout = $this->setLayout('select');
              echo $this->loadTemplate('shipment'); // lädt select_shipment.php
              $this->setLayout($previouslayout);
            ?>
          </div>
        </details>
      <?php else : ?>
        <?php
          // Fallback: wenn nicht OPC/select-layout: normaler Link
          echo JHtml::_(
            'link',
            JRoute::_('index.php?option=com_virtuemart&view=cart&task=edit_shipment', $this->useXHTML, $this->useSSL),
            'ändern',
            'class="fd-shipment-change-link"'
          );
        ?>
      <?php endif; ?>

    </div>
  </td>

  <td style="text-align:right;">(kostenlos)</td>
</tr>

<?php } ?> <!--Ende Auswahl Versandart/Abholstation-->

<?php if ($this->cart->pricesUnformatted['salesPrice']>0.0 and
	(VmConfig::get('oncheckout_opc',true) or
		!VmConfig::get('oncheckout_show_steps',false) or
		( (!VmConfig::get('oncheckout_opc',true) and VmConfig::get('oncheckout_show_steps',false) ) and !empty($this->cart->virtuemart_paymentmethod_id))
	)
) { ?>

<tr class="sectiontableentry1" style="vertical-align:top;">
	<td colspan="2" style="align:left;vertical-align:top;">
		<div class="fd-payment-head">
		  <div class="fd-payment-title">
			<?php echo '<h5>'.vmText::_('COM_VIRTUEMART_CART_SELECTED_PAYMENT').'</h5>'; ?>
			<div class="fd-payment-current">
			  <?php echo $this->cart->cartData['paymentName']; ?>
			</div>
		  </div>

		  <?php if (!empty($this->layoutName) && $this->layoutName == $this->cart->layout && VmConfig::get('oncheckout_opc', 0)) : ?>
			<details class="fd-payment-details" id="fdPaymentToggle">
			  <summary class="fd-payment-summary" role="button"><?php echo vmText::_('COM_VIRTUEMART_CART_BTN_SWITCH'); ?></summary>

			  <div class="fd-payment-body">
				<?php
				  $previouslayout = $this->setLayout('select');
				  echo $this->loadTemplate('payment'); // lädt select_payment.php
				  $this->setLayout($previouslayout);
				?>
			  </div>
			</details>
		  <?php else : ?>
			<?php
			  echo JHtml::_(
				'link',
				JRoute::_('index.php?option=com_virtuemart&view=cart&task=editpayment', $this->useXHTML, $this->useSSL),
				'ändern',
				'class="fd-payment-change-link"'
			  );
			?>
		  <?php endif; ?>
		</div>
	</td>
	<td style="text-align:right;">(kostenlos)</td>
</tr>

<?php } ?> <!--Ende Bezahl und Versandarten-->

<tr class="sectiontableentry2">
	<td colspan="2" style="text-align: right; border-bottom: none"><?php echo vmText::_ ('COM_VIRTUEMART_CART_TOTAL') ?>:</td>

	<td style="text-align: right; border-bottom: none"><strong><?php echo $this->currencyDisplay->createPriceDiv ('billTotal', '', $this->cart->cartPrices['billTotal'], FALSE); ?></strong></td>
</tr>




<?php
if ($this->totalInPaymentCurrency) {
?>
<tr class="sectiontableentry2">
	<td colspan="4" style="text-align: right;"><?php echo vmText::_ ('COM_VIRTUEMART_CART_TOTAL_PAYMENT') ?>:</td>
	<?php if (VmConfig::get ('show_tax')) { ?>
	<td style="text-align: right;">&nbsp;</td>
	<?php } ?>
	<td style="text-align: right;">&nbsp;</td>
	<td style="text-align: right;"><strong><?php echo $this->totalInPaymentCurrency;   ?></strong></td>
</tr>
	<?php
}

//Show VAT tax separated
if(!empty($this->cart->cartData)){
	if(!empty($this->cart->cartData['VatTax'])){
		$c = count($this->cart->cartData['VatTax']);
		if (!VmConfig::get ('show_tax') or $c>1) {
			if($c>0){ ?>

<tr class="sectiontableentry2">
	<td colspan="3">&nbsp;</td>
	<td colspan="2" style="text-align: left;border-bottom: 1px solid #333;"><?php echo vmText::_ ('COM_VIRTUEMART_TOTAL_INCL_TAX') ?></td>
	<?php if (VmConfig::get ('show_tax')) { ?>
	<td>&nbsp;</td>
	<?php } ?>
	<td>&nbsp;</td>
</tr>
			<?php
			}
			foreach( $this->cart->cartData['VatTax'] as $vatTax ) {
				if(!empty($vatTax['result'])) { ?>
<tr class="sectiontableentry<?php echo $i ?>">
	<td colspan="3">&nbsp;</td>
	<td style="text-align: right;"><?php echo shopFunctionsF::getTaxNameWithValue(vmText::_($vatTax['calc_name']),$vatTax['calc_value']) ?></td>
	<td style="text-align: right;"><span class="priceColor2"><?php echo $this->currencyDisplay->createPriceDiv( 'taxAmount', '', $vatTax['result'], FALSE, false, 1.0,false,true ) ?></span></td>
	<?php if (VmConfig::get ('show_tax')) { ?>
	<td >&nbsp;</td>
	<?php } ?>
	<td>&nbsp;</td>
</tr>

	
				<?php
				}
			}
		}
	}
}
/*
vmJsApi::addJScript( 'vmprices',false,false);

vmJsApi::vmVariables();
$onReady = 'jQuery(document).ready(function($) {

		Virtuemart.product($(".cart-summary"));
});';
vmJsApi::addJScript('ready.vmprices',$onReady);*/
?>
</table>
<?php

echo $this->loadTemplate ('cartfields');


// Checkout Button unten im Block (nur wenn vorhanden)
if (!empty($this->checkout_link_html)) {

  // Klassen hinzufügen (btn btn-primary w-100)
  // Falls schon class="" existiert, ergänzen wir. Sonst fügen wir class="" ein.
  $btn = $this->checkout_link_html;

  if (strpos($btn, 'class=') !== false) {
    $btn = preg_replace('/class=("|\')(.*?)\1/', 'class="$2 btn btn-primary w-100"', $btn, 1);
  } else {
    // class einfügen in erstes Tag
    $btn = preg_replace('/<([a-zA-Z0-9]+)/', '<$1 class="btn btn-primary w-100"', $btn, 1);
  }

  echo '<div class="mt-3">';
  echo $btn;
  echo '</div>';
}
?>
</div>
</div>
</div>

