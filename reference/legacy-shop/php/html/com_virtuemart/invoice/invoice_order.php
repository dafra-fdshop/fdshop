<?php
/**
 *
 * Order detail view
 *
 * @package	VirtueMart
 * @subpackage Orders
 * @author Oscar van Eijk, Valerie Isaksen
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: details_order.php 5341 2012-01-31 07:43:24Z alatak $
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
?>


 <table style="width:100%;" >
    <tr>
    <td>&nbsp;</td>
    </tr>
    <tr>
    <td style="width:50%">&nbsp;</td>
    <td style="width:45%">
    
    <table style="width:100%; border:2px solid black; border-collapse:collapse;" >
    
    <tr>
    <?php
		if ($this->doctype == 'invoice') {
			if ($this->orderDetails['details']['BT']->toPay != $this->orderDetails['details']['BT']->order_total) {
				$title = vmText::_('COM_VIRTUEMART_CREDIT_NOTE');
			} else {
				$title = vmText::_('COM_VIRTUEMART_INVOICE');
			}
			?>
			<td><?php echo $title; ?></td>
			<td align="left"><strong><?php echo $this->invoiceNumber; ?></strong></td>
		<?php 
		} elseif ($this->doctype == 'deliverynote') { ?>
			<td colspan="2"><?php echo vmText::_('COM_VIRTUEMART_DELIVERYNOTE'); ?></td>
		<?php 
		} elseif ($this->doctype == 'confirmation') { ?>
			<td colspan="2"><?php echo vmText::_('COM_VIRTUEMART_CONFIRMATION'); ?></td>
		<?php } ?>
	</tr>
    
	<?php if ($this->invoiceNumber) { ?>
    <tr>
	<td><?php echo vmText::_('COM_VIRTUEMART_INVOICE_DATE') ?></td>
	<td align="left"><?php echo vmJsApi::date($this->invoiceDate, 'LC4', true); ?></td>
    </tr>
	    <?php } ?>
	<?php if (!empty($this->orderDetails['details']['BT']->delivery_date)) { ?>
		<tr>
			<td class=""><?php echo vmText::_('COM_VIRTUEMART_DELIVERY_DATE') ?></td>
			<td align="left"><?php echo $this->orderDetails['details']['BT']->delivery_date ?></td>
		</tr>
	<?php } ?>
    <tr>
	<td ><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_PO_NUMBER') ?></td>
	<td align="left"><strong><?php echo $this->orderDetails['details']['BT']->order_number; ?></strong></td>
    </tr>

    <tr>
	<td class=""><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_PO_DATE') ?></td>
	<td align="left"><?php echo vmJsApi::date($this->orderDetails['details']['BT']->created_on, 'LC4', true); ?></td>
    </tr>

    <tr>
	<td valign="top"><br/></td>
    </tr>
</table>
</td>
</tr>
</table>



<table style="width:100%;" >
	
    <tr>
    <td style="height:70px;">&nbsp;</td>
    </tr>
    <tr>
    <td style="text-decoration:underline; font-size:8px;">FRANK DANIELs Pyro Design, Steinstrasse 13, 97950 Gerchsheim</td>
    </tr>
    <tr>
    <td><?php echo isset($this->userfields['fields']['company']['value']) ? $this->userfields['fields']['company']['value'] : ''; ?></td>
    </tr>
    <tr>
    <td><?php echo (isset($this->userfields['fields']['first_name']['value']) ? $this->userfields['fields']['first_name']['value'] : '') . ' ' .  (isset($this->userfields['fields']['last_name']['value']) ? $this->userfields['fields']['last_name']['value'] : ''); ?></td>
    </tr>
    <tr>
    <td><?php echo isset($this->userfields['fields']['address_1']['value']) ? $this->userfields['fields']['address_1']['value'] : ''; ?></td>
    </tr>
    <tr>
	<td><?php echo (isset($this->userfields['fields']['zip']['value']) ? $this->userfields['fields']['zip']['value'] : '') . ' ' . (isset($this->userfields['fields']['city']['value']) ? $this->userfields['fields']['city']['value'] : '');?></td>
    </tr>
    <tr>
	<td style="height:40px">&nbsp;</td>
    </tr>
    </table>
    
   
