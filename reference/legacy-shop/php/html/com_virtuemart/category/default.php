<?php
/**
 * VirtueMart Category View - modernized override
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

// 1. Web Asset Manager & Registry holen
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$registry = $wa->getRegistry();

// 2. Deine Registry-Datei explizit für dieses Dokument bekannt machen
// WICHTIG: Nutze den internen Namen deiner Komponente
$registry->addExtensionRegistryFile('com_favcom'); 

// 3. Jetzt kannst du deine Assets wie gewohnt nutzen
$wa->useStyle('com_favcom.addbtn')
   ->useScript('com_favcom.favcom');

$this->document->addScriptOptions('com_favcom', [
    'isGuest' => (bool) Factory::getUser()->guest,
    'msgGuest' => Text::_('COM_FAVCOM_MSG_COMBTN_GUEST'),
]);



// Dynamic product rendering (kept as in core)
if (vRequest::getInt('dynamic', false) && vRequest::getInt('virtuemart_product_id', false)) {
	if (!empty($this->products)) {
		if ($this->fallback) {
			$p = $this->products;
			$this->products = array();
			$this->products[0] = $p;
		}

		echo shopFunctionsF::renderVmSubLayout(
			$this->productsLayout,
			array(
				'products' => $this->products,
				'currency' => $this->currency,
				'products_per_row' => $this->perRow,
				'showRating' => $this->showRating
			)
		);
	}

	return;
}
?>

<div class="vm-category-view">
	
	<?php if ($this->show_store_desc && !empty($this->vendor->vendor_store_desc)) : ?>
		<div class="vm-vendor-desc">
			<?php echo $this->vendor->vendor_store_desc; ?>
		</div>
	<?php endif; ?>

	<?php
	// Category / Manufacturer description (optional)
	if (!empty($this->showcategory_desc) && empty($this->keyword)) : 

		if (!empty($this->manu_descr)) : ?>
			<div class="vm-manufacturer-desc">
				<?php echo $this->manu_descr; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php
	// Child categories (optional)
	if ($this->showcategory && empty($this->keyword) && !empty($this->category->has_children)) {
		echo ShopFunctionsF::renderVmSubLayout(
			'categories',
			array(
				'categories' => $this->category->children,
				'categories_per_row' => $this->categories_per_row
			)
		);
	}
	?>

	<?php if (!empty($this->products) || ($this->showsearch || $this->keyword !== false)) : ?>
		<div class="vm-browse-view">

			<?php
			// Search (kept, but no extra JS injected here)
			if ($this->showsearch || $this->keyword !== false) :
				$category_id = vRequest::getInt('virtuemart_category_id', 0);
				?>
				<div class="vm-search">
					<form action="<?php echo JRoute::_('index.php?option=com_virtuemart&view=category&limitstart=0', false); ?>" method="get">
						<?php if (!empty($this->searchCustomList)) : ?>
							<div class="vm-search-custom-list">
								<?php echo $this->searchCustomList; ?>
							</div>
						<?php endif; ?>

						<?php if (!empty($this->searchCustomValuesAr)) : ?>
							<div class="vm-search-custom-values">
								<?php
								echo ShopFunctionsF::renderVmSubLayoutAsGrid(
									'searchcustomvalues',
									array(
										'searchcustomvalues' => $this->searchCustomValuesAr,
										'options' => array(
											'items_per_row' => array('xs' => 2, 'sm' => 2, 'md' => 2, 'lg' => 2, 'xl' => 2),
										),
									)
								);
								?>
							</div>

							<?php if (count($this->searchCustomValuesAr) > 1) : ?>
								<div class="vm-search-combinetags">
									<?php
									echo vmText::_('COM_VM_COMBINETAGS');
									echo VmHtml::checkbox('combineTags', $this->combineTags, 1, 0, '', 'combineTags');
									?>
								</div>
							<?php endif; ?>
						<?php endif; ?>

						<div class="vm-search-row">
							<input name="keyword" class="vm-search-input" type="text" value="<?php echo vRequest::vmSpecialChars($this->keyword); ?>" />
							<button type="submit" class="vm-search-btn"><?php echo vmText::_('COM_VIRTUEMART_SEARCH'); ?></button>
							<span class="vm-search-descr"><?php echo vmText::_('COM_VM_SEARCH_DESC'); ?></span>
						</div>

						<input type="hidden" name="view" value="category" />
						<input type="hidden" name="option" value="com_virtuemart" />
						<input type="hidden" name="virtuemart_category_id" value="<?php echo (int) $category_id; ?>" />
						<input type="hidden" name="Itemid" value="<?php echo (int) $this->Itemid; ?>" />
					</form>
				</div>
			<?php endif; ?>

			<?php if (!empty($this->category->category_name)) : ?>
				<header class="vm-category-header">
					<h1 class="vm-category-title"><?php echo vmText::_($this->category->category_name); ?></h1>
				</header>
			<?php endif;
			
			    if (!empty($this->category->category_description)) : ?>
				<div class="vm-category-desc">
					<?php echo $this->category->category_description; ?>
				</div>
			<?php endif; ?>

			<?php
			// Toolbar: Sort / Pagination / Limit
			// IMPORTANT: We keep VM-generated HTML, but style it in a modern container (no JS hover needed).
			if (!empty($this->orderByList)) : ?>
				<div class="vm-toolbar vm-toolbar-top">

					<!-- Zeile 1: links Sortierung, rechts Pagination -->
					<div class="vm-toolbar-row vm-toolbar-row-1">
						<div class="vm-toolbar-left">
							<div class="vm-sort">
								<?php echo $this->orderByList['orderby']; ?>
								<?php echo $this->orderByList['manufacturer']; ?>
							</div>
						</div>

						<div class="vm-toolbar-center">
							<nav class="vm-pager" aria-label="Pagination">
								<?php echo $this->vmPagination->getPagesLinks(); ?>
							</nav>
						</div>
					</div>

					<!-- Zeile 2: rechts Results + Limit -->
					<div class="vm-toolbar-row vm-toolbar-row-2">
						<div class="vm-toolbar-right">
							<div class="vm-results">
								<div class="vm-results-count">
									<?php echo $this->vmPagination->getResultsCounter(); ?>
								</div>
								<div class="vm-limit">
									<?php echo $this->vmPagination->getLimitBox($this->category->limit_list_step); ?>
								</div>
							</div>
						</div>
					</div>

					<!-- Zeile 3: mittig Page-Counter -->
					<div class="vm-toolbar-row vm-toolbar-row-3">
						<div class="vm-page-counter">
							<?php echo $this->vmPagination->getPagesCounter(); ?>
						</div>
					</div>

				</div>
			<?php endif; ?>


			<?php
			// Products list
			if (!empty($this->products)) {

				// revert fallback behavior (kept)
				if ($this->fallback) {
					$p = $this->products;
					$this->products = array();
					$this->products[0] = $p;
				}

				echo shopFunctionsF::renderVmSubLayout(
					$this->productsLayout,
					array(
						'products' => $this->products,
						'currency' => $this->currency,
						'products_per_row' => $this->perRow,
						'showRating' => $this->showRating
					)
				);

				// Bottom pager
				if (!empty($this->orderByList)) : ?>
					<div class="vm-toolbar vm-toolbar-bottom">
						<div class="vm-toolbar-center">
							<div class="vm-page-counter">
								<?php echo $this->vmPagination->getPagesCounter(); ?>
							</div>
							<nav class="vm-pager" aria-label="Pagination">
								<?php echo $this->vmPagination->getPagesLinks(); ?>
							</nav>
						</div>
					</div>
				<?php endif;

			} elseif ($this->keyword !== false) {
				echo vmText::_('COM_VIRTUEMART_NO_RESULT') . ($this->keyword ? ' : (' . vRequest::vmSpecialChars($this->keyword) . ')' : '');
			}
			?>

			<?php
			// Keep VM JS output (needed for AddToCart/AJAX/etc.)
			echo vmJsApi::writeJS();
			?>

		</div>
	<?php endif; ?>

</div>
