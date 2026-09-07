<?php

/**
 * sublayout products mit CSS-Grid
 *
 * @package	VirtueMart
 * @author Daniel Frank
 */

defined('_JEXEC') or die('Restricted access');

/** @var array $viewData */
$productsPerRow = empty($viewData['products_per_row']) ? 1 : (int) $viewData['products_per_row'];
$currency       = $viewData['currency'] ?? null;
$showRating     = $viewData['showRating'] ?? null;

echo shopFunctionsF::renderVmSubLayout('askrecomjs');

$dynamic = (bool) (vRequest::getInt('dynamic', false) && vRequest::getInt('virtuemart_product_id', false));

/**
 * Fallback: wir brauchen keine VM-"RowHeights" mehr für Layout.
 * Damit die SubLayouts (prices/addtocart) trotzdem die erwarteten Keys haben:
 */
$rowHeights = [
	'product_s_desc' => 1,
	'price'          => 1,
	'customfields'   => 1,
];

/**
 * Render pro "type" (z.B. featured/latest/...) eine eigene Grid-Section.
 * Wenn wir nur "0" als type haben, kommt einfach eine Section ohne Überschrift.
 */
foreach (($viewData['products'] ?? []) as $type => $products) {
	
	// --- FavCom Compare Prefetch (einmal pro "type") ---
	$favcomCompareMap = [];

	try {
		// 1) Produkt-IDs sammeln
		$favcomProductIds = [];
		foreach ($products as $p) {
			if (is_object($p) && !empty($p->virtuemart_product_id)) {
				$favcomProductIds[] = (int) $p->virtuemart_product_id;
			}
		}

		// 2) Compare-Map prefetchen (1 Query)
		if (!empty($favcomProductIds)) {
			/** @var \DanielFrank\Component\FavCom\Site\Model\CompareModel $cmpModel */
			$cmpModel = \Joomla\CMS\MVC\Model\BaseDatabaseModel::getInstance(
				'Compare',
				'DanielFrank\\Component\\FavCom\\Site\\Model\\',
				['ignore_request' => true]
			);

			$favcomCompareMap = $cmpModel->prefetchCompareMap($favcomProductIds);
		}
	} catch (\Throwable $e) {
		// bewusst still (Dev kannst man loggen)
		$favcomCompareMap = [];
	}
	
	// --- FavCom Favorites Prefetch (einmal pro "type") ---
	$favcomFavoriteMap = [];

	try {
		if (!empty($favcomProductIds)) {
			/** @var \DanielFrank\Component\FavCom\Site\Model\FavoritModel $favModel */
			$favModel = \Joomla\CMS\MVC\Model\BaseDatabaseModel::getInstance(
				'Favorit',
				'DanielFrank\\Component\\FavCom\\Site\\Model\\',
				['ignore_request' => true]
			);

			$favcomFavoriteMap = $favModel->prefetchFavoriteMap($favcomProductIds);
		}
	} catch (\Throwable $e) {
		$favcomFavoriteMap = [];
	}
	

	if (empty($products) || !is_array($products)) {
		continue;
	}

	$hasTypeTitle = (!empty($type) && count($products) > 0) || (count($viewData['products']) > 1 && count($products) > 0);

	if ($hasTypeTitle) {
		$productTitle = vmText::_('COM_VIRTUEMART_' . strtoupper($type) . '_PRODUCT');
		?>
		<div class="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>-view">
			<h4><?php echo $productTitle; ?></h4>
		<?php
	}

	/**
	 * Einmal zentrieren (Cassiopeia-Style) und EIN Grid für alle Produkte dieses Typs.
	 * grid-child bleibt "Container", das eigentliche Grid ist vm-products-grid.
	 */
	?>
	<div class="grid-child">
		<div class="vm-products-grid" data-vm-products-per-row="<?php echo (int) $productsPerRow; ?>">
			<?php foreach ($products as $product) :

				$link = JRoute::_($product->link);
				?>
				<div class="product vm-product-card">
					<div class="spacers">
						<?php //var_dump(array_keys($product->customfieldsSorted ?? []));?>
						
						<div class="vm-product-media-container">
							<?php
							// Ribbons (Toparea)
							if (!empty($product->customfieldsSorted['toparea'])) {
								$position = 'toparea';
								echo '<div class="ribbons"> ' . shopFunctionsF::renderVmSubLayout(
									'customfields',
									['product' => $product, 'position' => $position]
								) . '</div>';
							}
							?>
							
							<?php
							
							$productid = (int) $product->virtuemart_product_id; 
							$productname = (string) $product->product_name;

							// Prefetch-Ergebnis auswerten (O(1))
							$favcomIsCompared = isset($favcomCompareMap[$productid]) ? 1 : 0;
							$favcomIsFavorited = isset($favcomFavoriteMap[$productid]) ? 1 : 0;?>
							
							<div class="circle_btns">
							<?php
							include(JPATH_ROOT . '/components/com_favcom/tmpl/sublayouts/addcomparebtn.php');
							include(JPATH_ROOT . '/components/com_favcom/tmpl/sublayouts/addfavoritesbtn.php');
							?>
							</div>

							<a title="<?php echo htmlspecialchars($product->product_name, ENT_QUOTES, 'UTF-8'); ?>"
							   href="<?php echo $link; ?>">
								<?php
								// Produktbild
								if (!empty($product->images[0])) {
									echo $product->images[0]->displayMediaThumb('class="browseProductImage"', false);
								}
								?>
							</a>
						</div>

						<div class="vm-product-descr-container-<?php echo (int) $rowHeights['product_s_desc']; ?>">
							<h5><?php echo JHtml::link($product->link, $product->product_name); ?></h5>

							<?php if (!empty($rowHeights['product_s_desc'])) : ?>
								<p class="product_s_desc">
									<?php
									if (!empty($product->product_s_desc)) {
										echo shopFunctionsF::limitStringByWord($product->product_s_desc, 60, ' ...');
									}
									?>
								</p>
							<?php endif; ?>
						</div>

						<?php
						$onbots = '';
						if (!empty($product->customfieldsSorted['onbot'][0]->customfield_value)) {
							$onbots = (string) $product->customfieldsSorted['onbot'][0]->customfield_value;
						}
						?>
						<div class=video_con>
							<div class="info_container">
								<a class="btn btn-primary" href="<?php echo $product->link; ?>" rel="nofollow">Details</a>

								<?php if (!empty($onbots)) :

									$doc = new DOMDocument();
									@$doc->loadHTML($onbots);
									$xpath  = new DOMXPath($doc);
									$iframe = $xpath->query('//iframe[@src]')->item(0);
									$src    = $iframe ? $iframe->getAttribute('src') : '';
									?>
									<a class="video_btn"
									   href="#"
									   data-bs-toggle="modal"
									   data-bs-target="#fdModal"
									   data-video-src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>"
									   rel="nofollow">
										<i class="fa fa-video-camera"></i>
									</a>
								<?php else : ?>
									<a class="novideo_btn" rel="nofollow"><i class="fa fa-video-camera"></i></a>
								<?php endif; ?>
							</div>
								<?php

								if (VmConfig::get('display_stock', 1)) :

								// 1) Stock-Level (normalstock / lowstock / nostock)
								$level = $product->stock->stock_level ?? 'nostock';

								// 2) "Im Lager" aus Customfield uvp holen (dein Layout-Key heißt uvp)
								$uvpVal = $product->customfieldsSorted['uvp'][0]->customfield_value ?? '';
								$imLager = (mb_strtolower(trim($uvpVal)) === 'im lager');

								// 3) Text-Mapping
								$mapLager = [
								  'normalstock' => 'Verfügbar',
								  'lowstock'    => 'wenige Verfügbar',
								  'nostock'     => 'Ausverkauft',
								];

								$mapBestell = [
								  'normalstock' => 'Bestellbar',
								  'lowstock'    => 'wenige Bestellbar',
								  'nostock'     => 'Ausverkauft',
								];

								// 4) Text wählen (nostock ist in beiden Fällen gleich)
								$txt = ($imLager ? ($mapLager[$level] ?? 'Ausverkauft') : ($mapBestell[$level] ?? 'Ausverkauft'));
								?>

								<div class="fdlager fdlager-<?php echo htmlspecialchars($level, ENT_QUOTES, 'UTF-8'); ?>">
								  <?php echo htmlspecialchars($txt, ENT_QUOTES, 'UTF-8'); ?>
								</div>
								<?php endif; ?>

						</div>

						<div class="detail-container">

							<?php
							// Benutzerfelder "spezi"
							echo shopFunctionsF::renderVmSubLayout(
								'customfields',
								['product' => $product, 'position' => 'spezi', 'class' => 'product-fields-spezi']
							);
							?>

							<div class="vm3pr-<?php echo (int) $rowHeights['price']; ?>">
								<?php echo shopFunctionsF::renderVmSubLayout('prices', ['product' => $product, 'currency' => $currency]); ?>
								<div class="clear"></div>
							</div>

							<div class="vm3pr-<?php echo (int) $rowHeights['customfields']; ?>">
								<?php
								echo shopFunctionsF::renderVmSubLayout(
									'addtocart',
									[
										'product'   => $product,
										'rowHeights'=> $rowHeights,
										'position'  => ['ontop', 'addtocart'],
									]
								);
								?>
							</div>

							<?php if ($dynamic) : ?>
								<?php echo vmJsApi::writeJS(); ?>
							<?php endif; ?>

						</div>

					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php

	if ($hasTypeTitle) {
		echo '</div>';
	}
}

// Wenn VM irgendwo JS gesammelt hat:
if (!$dynamic) {
	echo vmJsApi::writeJS();
}
?>