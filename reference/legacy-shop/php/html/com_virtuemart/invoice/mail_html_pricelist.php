<?php
/**
*
* Order items view
*
* @package	VirtueMart
* @subpackage Orders
* @author Max Milbers, Valerie Isaksen, Spirous Petrakis
* @link https://virtuemart.net
* @copyright Copyright (c) 2004 - 2018 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: details_items.php 5432 2012-02-14 02:20:35Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

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


$imageModel = VmModel::getModel('Product');

$colspan=8;

if ($this->doctype != 'invoice') {
    $colspan -= 4;
} elseif ( ! VmConfig::get('show_tax')) {
    $colspan -= 1;
}

$discountsBill = $this->discountsBill;
$taxBill = $this->taxBill;

?>

<table class="html-email" width="100%" cellspacing="0" cellpadding="5" border="0" style="border-collapse: collapse; margin: 0 auto;<?php echo $this->isMail ? ' font-family: Arial, Helvetica, sans-serif; font-size: 12px;' : ''; ?>">
	<tr style="text-align: left;" class="sectiontableheader">
		<th align="left" bgcolor="#EEEEEE" style="border: 1px solid #CCCCCC;"><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_SKU') ?></th>
		<th align="center" bgcolor="#EEEEEE" colspan="2" style="border: 1px solid #CCCCCC;"><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_NAME_TITLE') ?></th>
		<?php if ($this->doctype == 'invoice') { ?>
		<th align="center" bgcolor="#EEEEEE" style="border: 1px solid #CCCCCC;"><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_PRICE') ?></th>
		<?php } ?>
		<th align="center" bgcolor="#EEEEEE" style="border: 1px solid #CCCCCC;"><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_QTY') ?></th>
        <?php if ($this->doctype == 'invoice') { ?>
		<th align="center" bgcolor="#EEEEEE" style="border: 1px solid #CCCCCC;"><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_SUBTOTAL_DISCOUNT_AMOUNT') ?></th>
		<th align="right" bgcolor="#EEEEEE" style="border: 1px solid #CCCCCC;"><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_TOTAL') ?></th>
		<?php } ?>
	</tr>

<?php
$menuItemID = shopFunctionsF::getMenuItemId($this->orderDetails['details']['BT']->order_language);

VirtueMartModelCustomfields::$useAbsUrls = ($this->isMail or $this->isPdf);
foreach($this->orderDetails['items'] as $item) {
	$qtt = $item->product_quantity ;

    if ($this->print and !$this->isPdf) {
		$product_name = $item->order_item_name;;
	} else {
		$product_name = '<a href="'.JURI::root().'index.php?option=com_virtuemart&view=productdetails&virtuemart_category_id=' . $item->virtuemart_category_id .
		'&virtuemart_product_id=' . $item->virtuemart_product_id . '&Itemid=' . $menuItemID.'">'.$item->order_item_name.'</a>';
	}

	?>
	<tr style="vertical-align: top;">
		<td align="left" style="border: 1px solid #CCCCCC;">
			<?php echo $item->order_item_sku; ?>
		</td>
		<td align="left" style="border: 1px solid #CCCCCC;" colspan="2">
			<div>
			<?php
				
				if (VmConfig::get('oncheckout_show_images') && $this->isMail) {
					$productImages = $imageModel->getProduct($item->virtuemart_product_id);
					$imageModel->addImages($productImages);
				
					if (!empty($productImages)) {
						// PNG-Dateipfad und URL berechnen
						$thumbUrl = $productImages->images[0]->getFileUrlThumb();
						$pngUrl  = preg_replace('/\.\w+$/', '.png', JURI::root() . str_replace(JURI::root(), '', $thumbUrl));
						$pngPath = JPATH_ROOT . '/' . str_replace(JURI::root(), '', $pngUrl);
				
						// Fallback
						$fallback = dirname($pngUrl) . '/noPic.png';
				
						$image_path = file_exists($pngPath) ? $pngUrl : $fallback;
				
						echo '<img src="' . $image_path . '" width="50" alt="' . htmlspecialchars($item->order_item_name) . '"/>';
					}
				}
				
			?>
			<?php echo $product_name; ?></div>
			<?php
				$product_attribute = VirtueMartModelCustomfields::CustomsFieldOrderDisplay($item,'FE');
				echo $product_attribute;
			?>
		</td>
	<?php if ($this->doctype == 'invoice') { ?>
        <td align="right" style="border: 1px solid #CCCCCC;">
		
		<?php
			$item->product_discountedPriceWithoutTax = (float) $item->product_discountedPriceWithoutTax;
			if (!empty($item->product_priceWithoutTax) && $item->product_discountedPriceWithoutTax != $item->product_priceWithoutTax) {
				echo '<span style="text-decoration: line-through;">'.$this->currency->priceDisplay($item->product_basePriceWithTax, $this->user_currency_id) .'</span><br />';
				echo '<span >'.$this->currency->priceDisplay($item->product_final_price, $this->user_currency_id) .'</span><br />';
			} else {
				echo '<span >'.$this->currency->priceDisplay($item->product_final_price, $this->user_currency_id) .'</span><br />';
			}
		?>        
        </td>
		
	<?php } ?>
		<td align="right" style="border: 1px solid #CCCCCC;">
			<?php echo $qtt; ?>
		</td>
	<?php if ($this->doctype == 'invoice') { ?>
		<td align="right" style="border: 1px solid #CCCCCC;" class="priceCol" >
			<?php echo  $this->currency->priceDisplay( $item->product_subtotal_discount, $this->user_currency_id );  //No quantity is already stored with it ?>
		</td>
		<td align="right" style="border: 1px solid #CCCCCC;" class="priceCol">
			<?php
			$item->product_basePriceWithTax = (float) $item->product_basePriceWithTax;
			$class = '';
			if(!empty($item->product_basePriceWithTax) && $item->product_basePriceWithTax != $item->product_final_price ) {
				echo '<span style="text-decoration: line-through;">'.$this->currency->priceDisplay($item->product_basePriceWithTax,$this->user_currency_id,$qtt) .'</span><br />' ;
			}
			elseif (empty($item->product_basePriceWithTax) && $item->product_item_price != $item->product_final_price) {
				echo '<span style="text-decoration: line-through;">' . $this->currency->priceDisplay($item->product_item_price,$this->user_currency_id,$qtt) . '</span><br />';
			}

			echo $this->currency->priceDisplay(  $item->product_subtotal_with_tax ,$this->user_currency_id); //No quantity or you must use product_final_price ?>
		</td>
	<?php } ?>
	</tr>
<?php
} ?>

<?php if ($this->doctype == 'invoice') { ?>
	<tr class="sectiontableentry1">
		<td colspan="6" align="right" style="border: 1px solid #CCCCCC;"><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_PRODUCT_PRICES_TOTAL'); ?></td>
		<td align="right" style="border: 1px solid #CCCCCC;"><?php echo $this->currency->priceDisplay($this->orderDetails['details']['BT']->order_salesPrice, $this->user_currency_id) ?></td>
	</tr>
	
	<?php
	if ($this->orderDetails['details']['BT']->coupon_discount <> 0.00) {

    $couponDisplay = '';

    if (!empty($this->orderDetails['details']['BT']->coupon_code)) {
        $couponDisplay = ($fdAutoCouponEnabled && $fdAutoCouponLabel !== '')
            ? $fdAutoCouponLabel
            : $this->orderDetails['details']['BT']->coupon_code;

        $couponDisplay = ' (' . $couponDisplay . ')';
    }
	?>
	<tr>
		<td align="right" style="border: 1px solid #CCCCCC;" class="pricePad" colspan="6">
			<?php
			if ($fdAutoCouponEnabled && $fdAutoCouponLabel !== '') {
				echo $fdAutoCouponLabel;
			} else {
				echo vmText::_('COM_VIRTUEMART_COUPON_DISCOUNT') . $couponDisplay;
			}
			?>
		</td>
		<td align="right" style="border: 1px solid #CCCCCC;">
			<?php echo $this->currency->priceDisplay($this->orderDetails['details']['BT']->coupon_discount, $this->user_currency_id); ?>
		</td>
	</tr>
	<?php } ?>

	<?php
	if($discountsBill){
		foreach($discountsBill as $rule){ ?>
	<tr >
		<td colspan="6" align="right" style="border: 1px solid #CCCCCC;" class="pricePad"><?php echo $rule->calc_rule_name ?> </td>
		<?php if ( VmConfig::get('show_tax')) { ?>
		<td align="right" style="border: 1px solid #CCCCCC;">&nbsp;</td>
		<?php } ?>
		<td align="right" style="border: 1px solid #CCCCCC;"><?php echo $this->currency->priceDisplay($rule->calc_amount, $this->user_currency_id); ?></td>
		<td align="right" style="border: 1px solid #CCCCCC;"><?php echo $this->currency->priceDisplay($rule->calc_amount, $this->user_currency_id); ?></td>
	</tr>
			<?php
		}
	} ?>
	
    <tr>
		<td align="right" style="border: 1px solid #CCCCCC;" class="pricePad" colspan="6"><strong><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_TOTAL') ?></strong></td>
		<td align="right" style="border: 1px solid #CCCCCC;"><strong><?php echo $this->currency->priceDisplay($this->orderDetails['details']['BT']->order_total, $this->user_currency_id); ?></strong></td>
	</tr>
    
	<?php
		$rawPrice = $this->orderDetails['details']['BT']->order_total;
		$discountPriceMwStMail = round($rawPrice / 1.19 * 0.19, 2);
	?>
    <tr>
		<td align="right" style="border: 1px solid #CCCCCC;" class="pricePad" colspan="6"><?php echo vmText::_('enthaltene MwSt') ?></td>
		<td align="right" style="border: 1px solid #CCCCCC;"><?php echo $this->currency->priceDisplay($discountPriceMwStMail, $this->user_currency_id); ?></td>
	</tr>

	<?php
} ?>
</table>

<?php 
if ($this->doctype == 'invoice') { ?>
	<table class="html-email" width="100%" cellspacing="0" cellpadding="5" border="0" style="border-collapse: collapse; margin: 0 auto;<?php echo $this->isMail ? ' font-family: Arial, Helvetica, sans-serif; font-size: 12px;' : ''; ?>">
		<tr class="sectiontableheader">
			<th align="center" bgcolor="#EEEEEE" style="border: 1px solid #CCCCCC;">Versandinformationen</th>
			<th align="center" bgcolor="#EEEEEE" style="border: 1px solid #CCCCCC;">Bezahlinformationen</th>    
    	</tr>
    	<tr>
			<td align="center" style="border: 1px solid #CCCCCC;"><?php echo $this->orderDetails['shipmentName'] ?></td>
			<td align="center" style="border: 1px solid #CCCCCC;"><?php echo $this->orderDetails['paymentName'] ?></td>
		</tr>
	</table>
<?php
} ?>