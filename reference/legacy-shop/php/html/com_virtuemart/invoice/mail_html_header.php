<?php
/**
 *
 * Define here the Header for order mail success !
 *
 * @package    VirtueMart
 * @subpackage Cart
 * @author Kohl Patrick
 * @author Valérie Isaksen
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 *
 */
// Check to ensure this file is included in Joomla!
defined ('_JEXEC') or die('Restricted access');


?>
<table align="center" width="100%" border="0" cellpadding="10" cellspacing="0" class="html-email" style="border-collapse: collapse; font-family: Arial, Helvetica, sans-serif; font-size: 12px;">
<?php if ($this->vendor->vendor_letter_header > 0) { ?>
<tr>
<?php if ($this->vendor->vendor_letter_header_image > 0) { ?>
<td class="vmdoc-header-image" style="border: 1px solid #CCCCCC;" width="30%" align="center">
<a href="<?php echo JURI::root(); ?>" target="_blank">
<img src="<?php  echo JURI::root () . $this->vendor->images[0]->file_url ?>" border="0" width="280" style="display: block;" alt="<?php echo $this->vendor->vendor_store_name; ?>" />
</a>
</td>
<td width="70%" class="vmdoc-header-vendor" style="border: 1px solid #CCCCCC;">
<?php } else { // no image ?>
<td width="580" class="vmdoc-header-vendor" style="border: 1px solid #CCCCCC;">
<?php } ?>
<div id="vmdoc-header" class="vmdoc-header">
<?php echo VirtuemartViewInvoice::replaceVendorFields ($this->vendor->vendor_letter_header_html, $this->vendor); ?>
</div>
</td>
</tr>
<?php } // END if header ?>
<tr>
<td width="580" colspan="2" style="border: 1px solid #CCCCCC;">
<strong><?php echo vmText::sprintf ('COM_VIRTUEMART_MAIL_SHOPPER_NAME', $this->civility . ' ' . $this->orderDetails['details']['BT']->first_name . ' ' . $this->orderDetails['details']['BT']->last_name); ?></strong>
</td>
</tr>
</table>