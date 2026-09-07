<?php
/**
* sublayout products
*
* @package	VirtueMart
* @author Max Milbers
* @link https://virtuemart.net
* @copyright Copyright (c) 2014 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL2, see LICENSE.php
* @version $Id: cart.php 7682 2014-02-26 17:07:20Z Milbo $
*/

defined('_JEXEC') or die('Restricted access');

$product = $viewData['product'];
$position = $viewData['position'];
$customTitle = isset($viewData['customTitle'])? $viewData['customTitle']: false;;
if(isset($viewData['class'])){
	$class = $viewData['class'];
} else {
	$class = 'product-fields';
}	

if (!empty($product->customfieldsSorted[$position])) {
	?>
	<div class="<?php echo $class?>">
		<?php
		if($customTitle and isset($product->customfieldsSorted[$position][0])){
			$field = $product->customfieldsSorted[$position][0]; ?>
		<div class="product-fields-title-wrapper"><span class="product-fields-title"><strong><?php echo vmText::_ ($field->custom_title) ?></strong></span>
			<?php if ($field->custom_tip) {
				echo JHtml::tooltip (vmText::_($field->custom_tip), vmText::_ ($field->custom_title), 'tooltip.png');
			} ?>
		</div> <?php
		}
		$custom_title = null;
		foreach ($product->customfieldsSorted[$position] as $field) {
			if ( $field->is_hidden || empty($field->display)) continue; //OSP http://forum.virtuemart.net/index.php?topic=99320.0
			
			if ($field->layout_pos == 'spezi') {
			?><div class="benutzerfeld">
            	
                <div class="benutzerfeld-<?php echo $field->custom_title ?>">
                <?php echo vmText::_($field->display); ?>
                </div>
              </div>
              
            <?php
			
			}
			else {
			
			?><div class="product-field product-field-type-<?php echo $field->field_type ?>">
				<?php if (!$customTitle and $field->custom_title != $custom_title and $field->show_title) { 
						$tipText  = trim(vmText::_($field->custom_tip));
						$tipTitle = trim(vmText::_($field->custom_title));
					?>
					<span class="product-fields-title-wrapper"><span class="product-fields-title"><strong><?php echo vmText::_ ($field->custom_title) ?></strong></span>
						<?php if ($field->custom_tip) {
							//echo JHtml::tooltip (vmText::_($field->custom_tip), vmText::_ ($field->custom_title), 'tooltip.png');?>
							<span class="fd-tip" tabindex="0" aria-label="<?php echo htmlspecialchars($tipTitle . ': ' . $tipText, ENT_QUOTES, 'UTF-8'); ?>">
							  <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
							  <span class="fd-tip__bubble" role="tooltip">
								<?php echo htmlspecialchars($tipText, ENT_QUOTES, 'UTF-8'); ?>
							  </span>
							</span>
					<?php } ?></span>
				<?php }
				if (!empty($field->display)){
					?><div class="product-field-display <?php if($field->custom_title=='sale') echo vmText::_ ($field->custom_title);?> <?php if($field->custom_title=='hot') echo vmText::_ ($field->custom_title);?> <?php if($field->custom_title=='new') echo vmText::_ ($field->custom_title);?><?php if($field->custom_title=='schinken') echo vmText::_ ($field->custom_title);?><?php if($field->custom_title=='chargen') echo vmText::_ ($field->custom_title);?><?php if($field->custom_title=='display') echo vmText::_ ($field->custom_title);?>"><?php echo $field->display ?></div><?php
				}
				if (!empty($field->custom_desc)){
					?><div class="product-field-desc"><?php echo vmText::_($field->custom_desc) ?></div> <?php
				}
				?>
			</div>
		<?php
			$custom_title = $field->custom_title;
		}//ende Else anweisung 
		}//ende if anweisung
		?>
      <div class="clear"></div>
	</div>
<?php
} ?>