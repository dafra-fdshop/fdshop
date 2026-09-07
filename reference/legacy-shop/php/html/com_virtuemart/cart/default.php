<?php
/**
 *
 * Layout for the shopping cart
 *
 * @package    VirtueMart
 * @subpackage Cart
 * @author Max Milbers
 *
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2020 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: cart.php 2551 2010-09-30 18:52:40Z milbo $
 */

// Check to ensure this file is included in Joomla!
defined ('_JEXEC') or die('Restricted access');

vmJsApi::vmValidator();
//vmdebug('my cart',$this->cart);
?>

<div class="vm-cart-header-container">
	<div class="width50 floatleft vm-cart-header">
		<h1><?php echo vmText::_ ('COM_VIRTUEMART_CART_TITLE'); ?></h1>
		<div class="payments-signin-button" ></div>
	</div>
	<?php if (VmConfig::get ('oncheckout_show_steps', 1) ){
		if($this->checkout_task == 'checkout') {
			echo '<div class="checkoutStep" id="checkoutStep1">' . vmText::_ ('COM_VIRTUEMART_USER_FORM_CART_STEP1') . '</div>';
		} else { //if($this->checkout_task == 'confirm') {
			echo '<div class="checkoutStep" id="checkoutStep4">' . vmText::_ ('COM_VIRTUEMART_USER_FORM_CART_STEP4') . '</div>';
		}
	}  ?>
	
	<div class="clear"></div>
</div>

<div id="cart-view" class="cart-view">


	<?php
	$uri = vmUri::getCurrentUrlBy('get');
	$uri = str_replace(array('?tmpl=component','&tmpl=component'),'',$uri);
	//echo shopFunctionsF::getLoginForm ($this->cart, FALSE,$uri);

	

	$taskRoute = '';
	?><form method="post" id="checkoutForm" name="checkoutForm" action="<?php echo JRoute::_ ('index.php?option=com_virtuemart&view=cart' . $taskRoute, $this->useXHTML, $this->useSSL); ?>">
		<?php
		if(!$this->isPdf and VmConfig::get('multixcart')=='byselection'){
			echo shopFunctions::renderVendorFullVendorList($this->cart->vendorId);
			?><input type="submit" name="updatecart" title="<?php echo vmText::_('COM_VIRTUEMART_SAVE'); ?>" value="<?php echo vmText::_('COM_VIRTUEMART_SAVE'); ?>" class="button"  style="margin-left: 10px;"/><?php
		}
		//echo $this->loadTemplate ('address');
		// This displays the pricelist MUST be done with tables, because it is also used for the emails
		echo $this->loadTemplate ('pricelist');

		if (!empty($this->checkoutAdvertise)) {
			?> <div id="checkout-advertise-box"> <?php
			foreach ($this->checkoutAdvertise as $checkoutAdvertise) {
				?>
				<div class="checkout-advertise">
					<?php echo $checkoutAdvertise; ?>
				</div>
			<?php
			}
			?></div><?php
		}

		// Continue and Checkout Button END ?>
		<input type='hidden' name='order_language' value='<?php echo $this->order_language; ?>'/>
		<input type='hidden' name='task' value='updatecart'/>
		<input type='hidden' name='option' value='com_virtuemart'/>
		<input type='hidden' name='view' value='cart'/>
		<input type="hidden" name="<?php echo vRequest::getFormToken(); ?>" value="1" />
	</form>


<?php





vmJsApi::addJScript('vm-checkoutFormSubmit',"
Virtuemart.bCheckoutButton = function(e) {
	e.preventDefault();
	jQuery(this).vm2front('startVmLoading');
	jQuery(this).attr('disabled', 'true');
	jQuery(this).removeClass( 'vm-button-correct' );
	jQuery(this).addClass( 'vm-button' );
	jQuery(this).fadeIn( 400 );
	var name = jQuery(this).attr('name');
	var div = '<input name=\"'+name+'\" value=\"1\" type=\"hidden\">';
    //if(name=='confirm'){  //What is this?
        //jQuery('#checkoutForm').attr('action','".$this->orderDoneLink."');
    //}
	jQuery('#checkoutForm').append(div);
	//Virtuemart.updForm();
	
	jQuery('#checkoutForm').submit();
}
jQuery(document).ready(function($) {
	jQuery(this).vm2front('stopVmLoading');
	var el = jQuery('#checkoutFormSubmit');
	el.unbind('click dblclick');
	el.on('click dblclick',Virtuemart.bCheckoutButton);
});
	");

if( !VmConfig::get('oncheckout_ajax',false)) {
	vmJsApi::addJScript('vm-STisBT',"
		jQuery(document).ready(function($) {

			if ( $('#STsameAsBTjs').is(':checked') ) {
				$('#output-shipto-display').hide();
			} else {
				$('#output-shipto-display').show();
			}
			$('#STsameAsBTjs').click(function(event) {
				if($(this).is(':checked')){
					$('#STsameAsBT').val('1') ;
					$('#output-shipto-display').hide();
				} else {
					$('#STsameAsBT').val('0') ;
					$('#output-shipto-display').show();
				}
				var form = jQuery('#checkoutFormSubmit');
				form.submit();
			});
		});
	");
}

$this->addCheckRequiredJs();
vmJsApi::addJScript( 'vmprices',false,false);

?><div style="display:none;" id="cart-js">
<?php echo vmJsApi::writeJS(); ?>
</div>
</div>