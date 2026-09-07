<?php
/**
 *
 * Show the product details page
 *
 * @package	VirtueMart
 * @subpackage
 * @author Max Milbers, Eugen Stranz, Max Galt
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2014 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: default.php 10982 2024-03-18 08:58:44Z  $
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/* Let's see if we found the product */
if (empty($this->product)) {
	echo vmText::_('COM_VIRTUEMART_PRODUCT_NOT_FOUND');
	echo '<br /><br />  ' . $this->continue_link_html;
	return;
}

echo shopFunctionsF::renderVmSubLayout('askrecomjs',array('product'=>$this->product));

//vmdebug('My product',$this->product->loadFieldValues());

if(vRequest::getInt('print',false)){ ?>
<body onLoad="javascript:print();">
<?php } ?>

<div class="product-container productdetails-view productdetails">

	<?php
	// Product Navigation
	if (VmConfig::get('product_navigation', 1)) {
	?>
		<div class="product-neighbours">
		<?php
		if (!empty($this->product->neighbours ['previous'][0])) {
		$prev_link = JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $this->product->neighbours ['previous'][0] ['virtuemart_product_id'] . '&virtuemart_category_id=' . $this->product->virtuemart_category_id, FALSE);
		echo JHtml::_('link', $prev_link, $this->product->neighbours ['previous'][0]
			['product_name'], array('rel'=>'prev', 'class' => 'previous-page','data-dynamic-update' => '1'));
		}
		if (!empty($this->product->neighbours ['next'][0])) {
		$next_link = JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $this->product->neighbours ['next'][0] ['virtuemart_product_id'] . '&virtuemart_category_id=' . $this->product->virtuemart_category_id, FALSE);
		echo JHtml::_('link', $next_link, $this->product->neighbours ['next'][0] ['product_name'], array('rel'=>'next','class' => 'next-page','data-dynamic-update' => '1'));
		}
		?>
		<div class="clear"></div>
		</div>
	<?php } // Product Navigation END
	?>

	<?php // afterDisplayTitle Event
	echo $this->product->event->afterDisplayTitle ?>

	<?php
	// Product Edit Link
	echo $this->edit_link;
	// Product Edit Link END
	?>

	<?php
	// PDF - Print - Email Icon
	if (VmConfig::get('show_emailfriend') || VmConfig::get('show_printicon') || VmConfig::get('pdf_icon')) {
	?>
		<div class="icons">
		<?php

		$link = 'index.php?tmpl=component&option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $this->product->virtuemart_product_id;

		echo $this->linkIcon($link . '&format=pdf', 'COM_VIRTUEMART_PDF', 'pdf_button', 'pdf_icon', false);
		//echo $this->linkIcon($link . '&print=1', 'COM_VIRTUEMART_PRINT', 'printButton', 'show_printicon');
		echo $this->linkIcon($link . '&print=1', 'COM_VIRTUEMART_PRINT', 'printButton', 'show_printicon',false,true,false,'class="printModal"');
		$MailLink = 'index.php?option=com_virtuemart&view=productdetails&task=recommend&virtuemart_product_id=' . $this->product->virtuemart_product_id . '&virtuemart_category_id=' . $this->product->virtuemart_category_id . '&tmpl=component';
		echo $this->linkIcon($MailLink, 'COM_VIRTUEMART_EMAIL', 'emailButton', 'show_emailfriend', false,true,false,'class="recommened-to-friend"');
		?>
		<div class="clear"></div>
		</div>
	<?php } // PDF - Print - Email Icon END
	
	echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'ontop'));
	?>

	<div class="vm-product-container">
	<div class="vm-product-media-container">
    	<div class="spacers">
			
			
			<?php
			// ACHTUNG DEISEN TEIL NOCH MAL ANSCHAUEN!!! WURDE NUR SCHNELL GEFIXT!
            /** $position = 'toparea';
             echo "<div class=\"ribbons\"> ".shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position' => $position))."</div>";
			 */
             ?>
			
			<?php
			// ACHTUNG DEISEN TEIL NOCH MAL ANSCHAUEN!!! WURDE NUR SCHNELL GEFIXT!
				$rib = trim((string) shopFunctionsF::renderVmSubLayout(
				  'customfields',
				  array('product' => $this->product, 'position' => 'toparea')
				));

				if ($rib === '') {
				  // Fallback, damit du heute sofort was siehst
				  $rib = trim((string) shopFunctionsF::renderVmSubLayout(
					'customfields',
					array('product' => $this->product, 'position' => 'ontop')
				  ));
				}

				echo '<div class="ribbons">' . $rib . '</div>';
			?>
     	
		<?php
        echo $this->loadTemplate('images');
        ?>
			<div class="button-wrapper-product">
				<?php
				$productid = $this->product->virtuemart_product_id; 
				$productname = $this->product->product_name;
				//Buttons über dem Produktbild positioniert 
				include(JPATH_ROOT . '/components/com_favcom/tmpl/sublayouts/addcomparebtn.php');
				include(JPATH_ROOT . '/components/com_favcom/tmpl/sublayouts/addfavoritesbtn.php');

				?>
			</div>
        </div>
	</div>

	<div class="vm-product-details-container">
    	
        <div class="detail-padding"> 

            <div class="product-details-header">
            	<?php // Product Title   ?>
                <h1 class="product-title"><?php echo $this->product->product_name ?></h1>
                <?php // Product Title END
				
				//Hier die Bewertung vom VM
				//$fd_rating = $this->product->rating;
				$fd_rating = (float) ($this->product->rating ?? 0);

				// Clamp 0..5
				if ($fd_rating < 0) $fd_rating = 0;
				if ($fd_rating > 5) $fd_rating = 5;

				// Prozent für die gefüllte Ebene (0..100)
				$fd_fill = ($fd_rating / 5) * 100;

				// Optional: auf 1 Nachkommastelle im Tooltip
				$fd_rating_txt = number_format($fd_rating, 1, ',', '');
				?>

				<div class="fd_rating_container"
					 role="img"
					 aria-label="<?php echo $fd_rating > 0 ? ('Bewertung: ' . $fd_rating_txt . ' von 5') : 'Noch keine Bewertung'; ?>"
					 title="<?php echo $fd_rating > 0 ? ($fd_rating_txt . ' / 5') : 'Noch keine Bewertung'; ?>">
					
					  <div class="fd_rating_wrapper">
						<div class="fd_rating_base">
						  <i class="fa-regular fa-star"></i>
						  <i class="fa-regular fa-star"></i>
						  <i class="fa-regular fa-star"></i>
						  <i class="fa-regular fa-star"></i>
						  <i class="fa-regular fa-star"></i>
						</div>

						<div class="fd_rating_fill" style="width: <?= $fd_fill ?>%">
						  <i class="fa-solid fa-star"></i>
						  <i class="fa-solid fa-star"></i>
						  <i class="fa-solid fa-star"></i>
						  <i class="fa-solid fa-star"></i>
						  <i class="fa-solid fa-star"></i>
						</div>
					  </div>

				  <?php if ($fd_rating > 0): ?>
					<span class="fd_rating_value"><?php echo $fd_rating_txt; ?></span>
				  <?php endif; ?>
				</div> 			              
            </div>
            <hr class="product-separator">

			<?php
            // Product Short Description
            if (!empty($this->product->product_s_desc)) {
            ?>
                <div class="product-short-description">
                <?php
                /** @todo Test if content plugins modify the product description */
                echo nl2br($this->product->product_s_desc);
                ?>
                </div>
            <?php
            } // Product Short Description END
            ?>
            <hr class="product-separator">
            
            <?php
            // Hier werden Die Felder (Kaliber etc.) geladen. Sie werden mittels customfields aus dem Sublayout-Ordner geladen.
			echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'spezi','class'=>'product-fields-spezi'));
            ?>
            <hr class="product-separator">
            <?php
            echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'onbot'));
            ?>
            <hr class="product-separator">
            <?php
            echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'uvp'));
            ?>
            <hr class="product-separator">
            
            <div class="buy-area">
    
            <?php
            // TODO in Multi-Vendor not needed at the moment and just would lead to confusion
            /* $link = JRoute::_('index2.php?option=com_virtuemart&view=virtuemart&task=vendorinfo&virtuemart_vendor_id='.$this->product->virtuemart_vendor_id);
              $text = vmText::_('COM_VIRTUEMART_VENDOR_FORM_INFO_LBL');
              echo '<span class="bold">'. vmText::_('COM_VIRTUEMART_PRODUCT_DETAILS_VENDOR_LBL'). '</span>'; ?><a class="modal" href="<?php echo $link ?>"><?php echo $text ?></a><br />
             */
            ?>
    
            <?php
            
    
            foreach ($this->productDisplayTypes as $type=>$productDisplayType) {
    
                foreach ($productDisplayType as $productDisplay) {
    
                    foreach ($productDisplay as $virtuemart_method_id =>$productDisplayHtml) {
                        ?>
                        <div class="<?php echo substr($type, 0, -1) ?> <?php echo substr($type, 0, -1).'-'.$virtuemart_method_id ?>">
                            <?php
                            echo $productDisplayHtml;
                            ?>
                        </div>
                        <?php
                    }
                }
            }
    
            //In case you are not happy using everywhere the same price display fromat, just create your own layout
            //in override /html/fields and use as first parameter the name of your file
            echo shopFunctionsF::renderVmSubLayout('prices',array('product'=>$this->product,'currency'=>$this->currency));
            ?> <div class="clear"></div><?php
            echo shopFunctionsF::renderVmSubLayout('addtocart',array('product'=>$this->product));
    
            echo shopFunctionsF::renderVmSubLayout('stockhandle',array('product'=>$this->product));
    
            // Ask a question about this product
            if (VmConfig::get('ask_question', 0) == 1) {
                $askquestion_url = JRoute::_('index.php?option=com_virtuemart&view=productdetails&task=askquestion&virtuemart_product_id=' . $this->product->virtuemart_product_id . '&virtuemart_category_id=' . $this->product->virtuemart_category_id . '&tmpl=component', FALSE);
                ?>
                <div class="ask-a-question">
                    <a class="ask-a-question" href="<?php echo $askquestion_url ?>" rel="nofollow" ><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_ENQUIRY_LBL') ?></a>
                </div>
            <?php
            }
            ?>
    
            <?php
            // Manufacturer of the Product
            if (VmConfig::get('show_manufacturers', 1) && !empty($this->product->virtuemart_manufacturer_id)) {
                echo $this->loadTemplate('manufacturer');
            }
            ?>
    
            </div>
        </div>   
	</div>
	<div class="clear"></div>


	</div>
<?php
	$count_images = count ($this->product->images);
	if ($count_images > 1) {
		echo $this->loadTemplate('images_additional');
	}

	// event onContentBeforeDisplay
	echo $this->product->event->beforeDisplayContent; ?>
    
    <!-- 1. Nav-Leiste mit zwei Tabs -->
    <ul class="nav nav-tabs" id="myTab" role="tablist">
      <li class="nav-item" role="presentation">
        <button
          class="nav-link active"
          id="tab1-tab"
          data-bs-toggle="tab"
          data-bs-target="#tab1"
          type="button"
          role="tab"
          aria-controls="tab1"
          aria-selected="true"
        >
          <?php echo vmText::_('COM_VIRTUEMART_PRODUCT_DESC_TITLE') ?>
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button
          class="nav-link"
          id="tab2-tab"
          data-bs-toggle="tab"
          data-bs-target="#tab2"
          type="button"
          role="tab"
          aria-controls="tab2"
          aria-selected="false"
        >
          <?php echo vmText::_( "COM_VIRTUEMART_RATING_TITLE" ) ?>
        </button>
      </li>
    </ul>
    
    <!-- 2. Tab-Inhalte -->
    <div class="tab-content" id="myTabContent">
      <!-- Erstes Pane (sichtbar) -->
      <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="tab1-tab">        
			<?php
            //echo ($this->product->product_in_stock - $this->product->product_ordered);
            // Product Description
            if (!empty($this->product->product_desc)) {
                ?>
                <div class="product-description" > 
            <?php echo $this->product->product_desc; ?>
                </div>
            <?php
            } // Product Description END
            ?>
      </div>
    
      <!-- Zweites Pane (versteckt, bis „Registerkarte 2“ geklickt wird) -->
      <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="tab2-tab">
        
        <?php echo $this->loadTemplate('reviews'); ?>
		
      </div>
    </div>

	
	<?php
	echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'normal'));

	// Product Packaging
	$product_packaging = '';
	if ($this->product->product_box) {
	?>
		<div class="product-box">
		<?php
			echo vmText::_('COM_VIRTUEMART_PRODUCT_UNITS_IN_BOX') .$this->product->product_box;
		?>
		</div>
	<?php } // Product Packaging END
	

	echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'related_products','class'=> 'product-related-products','customTitle' => true ));

	echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'related_categories','class'=> 'product-related-categories'));

	?>

<?php // onContentAfterDisplay event
echo $this->product->event->afterDisplayContent;

// Show child categories
if ($this->cat_productdetails)  {
	echo $this->loadTemplate('showcategory');
}

$j = 'jQuery(document).ready(function($) {
	$("form.js-recalculate").each(function(){
		if ($(this).find(".product-fields").length && !$(this).find(".no-vm-bind").length) {
			var id= $(this).find(\'input[name="virtuemart_product_id[]"]\').val();
			Virtuemart.setproducttype($(this),id);

		}
	});
});';
//vmJsApi::addJScript('recalcReady',$j);

if(VmConfig::get ('jdynupdate', TRUE)){

	/** GALT
	 * Notice for Template Developers!
	 * Templates must set a Virtuemart.container variable as it takes part in
	 * dynamic content update.
	 * This variable points to a topmost element that holds other content.
	 */
/*	$j = "Virtuemart.container = jQuery('.productdetails-view');
Virtuemart.containerSelector = '.productdetails-view';
//Virtuemart.recalculate = true;	//Activate this line to recalculate your product after ajax
";

	vmJsApi::addJScript('ajaxContent',$j);*/

	$j = "jQuery(document).ready(function($) {
	Virtuemart.stopVmLoading();
	var msg = '';
	$('a[data-dynamic-update=\"1\"]').off('click', Virtuemart.startVmLoading).on('click', {msg:msg}, Virtuemart.startVmLoading);
	$('[data-dynamic-update=\"1\"]').off('change', Virtuemart.startVmLoading).on('change', {msg:msg}, Virtuemart.startVmLoading);
});";

	vmJsApi::addJScript('vmPreloader',$j);
}

if ($this->product->prices['salesPrice'] > 0) {
	echo shopFunctionsF::renderVmSubLayout('snippets',array('product'=>$this->product, 'currency'=>$this->currency, 'showRating'=>$this->showRating));
}

// Back To Category Button
if ($this->product->virtuemart_category_id) {
	$catURL =  JRoute::_('index.php?option=com_virtuemart&view=category&virtuemart_category_id='.$this->product->virtuemart_category_id, FALSE);
	$categoryName = vmText::_($this->product->category_name) ;
} else {
	$catURL =  JRoute::_('index.php?option=com_virtuemart');
	$categoryName = vmText::_('COM_VIRTUEMART_SHOP_HOME') ;
}
?>
<div class="back-to-category">
	<a href="<?php echo $catURL ?>" class="product-details" title="<?php echo $categoryName ?>"><?php echo vmText::sprintf('COM_VIRTUEMART_CATEGORY_BACK_TO',$categoryName) ?></a>
</div> 
<?php // End Back To Category Button

echo vmJsApi::writeJS();
?>
</div>