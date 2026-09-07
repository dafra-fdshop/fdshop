<?php
/**
*
* Modify user form view
*
* @package	VirtueMart
* @subpackage User
* @author Oscar van Eijk, Max Milbers, Stan
* @link https://virtuemart.net
* @copyright Copyright (c) 2004 - 2020 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: edit.php 11094 2024-11-11 12:05:51Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

vmJsApi::css('vmpanels'); // VM_THEMEURL

$url = vmURI::getCurrentUrlBy('request');
$cancelUrl = JRoute::_($url.'&task=cancel');

$showRegisterText = $this->showRegisterText;
$showRegistration = $this->showRegistration;


?>
<div class="container-register">
    <div class="container-content titel">
		<h4><?php //echo $this->page_title ?></h4>
		<div class="alert alert-warning" role="alert">
			<strong>INFO:</strong> Vielen Dank, dass Sie sich bei uns anmelden m&ouml;chten.<br>
			Sie erhalten danach eine E-Mail mit einem Aktivierungslink (bitte pr&uuml;fen Sie ggf. auch Ihren Spam-Ordner).<br>
			Erst nachdem Sie diesen Link angeklickt und Ihre E-Mail-Adresse best&auml;tigt haben, k&ouml;nnen Sie sich in Ihrem Kundenkonto anmelden.
		</div>
    </div>
	
	<div class="container-second">
        <div class="container-content login">
			<?php echo shopFunctionsF::getLoginForm(false,false); ?>
		</div>
		
		<div class="container-content fields">

			<?php if($this->userDetails->virtuemart_user_id==0) {
				echo '<h4>'.vmText::_('COM_VIRTUEMART_YOUR_ACCOUNT_REG').'</h4>';
			}

			?>
			<form method="post" id="adminForm" name="userForm" action="<?php echo JRoute::_($url) ?>" class="form-validate">
			
			<?php // Loading Templates in Tabs
			if($this->userDetails->virtuemart_user_id!=0) {
				$tabarray = array();

				$tabarray['shopper'] = 'COM_VIRTUEMART_SHOPPER_FORM_LBL';

				if(!empty($this->manage_link)) {
					echo $this->manage_link;
				}

				if(!empty($this->add_product_link)) {
					echo $this->add_product_link;
				}

				if($this->userDetails->user_is_vendor){

					$tabarray['vendor'] = 'COM_VIRTUEMART_VENDOR';
				}

				//$tabarray['user'] = 'COM_VIRTUEMART_USER_FORM_TAB_GENERALINFO';
				if (!empty($this->shipto)) {
					$tabarray['shipto'] = 'COM_VIRTUEMART_USER_FORM_ADD_SHIPTO_LBL';
				}
				if (($_ordcnt = count($this->orderlist)) > 0) {
					$tabarray['orderlist'] = 'COM_VIRTUEMART_YOUR_ORDERS';
				}

				shopFunctionsF::buildTabs ( $this, $tabarray);

			 } else {
				if($showRegistration) {
					echo $this->loadTemplate ( 'shopper' );
				} else {

				}
				//echo $this->captcha;
				//captcha addition

			 }

			//stAn - with hidden config of reg_captcha_logged=1 we can trigger captcha for logged in if needed as well, or add user input filter plugin for antispam
			echo $this->captcha;

			// end of captcha addition
				  
			
			if($showRegistration){ ?>
				<div class="buttonBar-right">
				<button class="btn btn-primary" type="submit" onclick="javascript:return myValidator(userForm, true);" ><?php echo $this->button_lbl ?></button>
				&nbsp;
			<button class="btn btn-secondary" type="reset" onclick="window.location.href='<?php echo $cancelUrl ?>'" ><?php echo vmText::_('COM_VIRTUEMART_CANCEL'); ?></button></div>
				 
			<?php } ?>	
			
			<input type="hidden" name="option" value="com_virtuemart" />
			<input type="hidden" name="controller" value="user" />
			<?php echo JHtml::_( 'form.token' ); ?>
			</form>
		</div>
	</div>
</div>
