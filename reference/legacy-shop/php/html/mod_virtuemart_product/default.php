<?php
defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

$carouselId = 'fd-vmcarousel-' . (int) $module->id;
$perSlide   = 5;

$slides      = array_chunk($products, $perSlide);
$hasControls = count($slides) > 1;
 
$categoryLink = Route::_('index.php?option=com_virtuemart&view=category&virtuemart_category_id=' . $products[0]->virtuemart_category_id );

?>

<div id="<?php echo $carouselId; ?>"
     class="carousel slide fd-vmcarousel"
     data-bs-touch="true"
     data-bs-interval="false">
	
	<div class="fd-vmcarousel-controls" aria-label="Karussell Navigation">
		<a href="<?php echo $categoryLink; ?>"
		   class="btn btn-primary btn-sm fd-vmcarousel-btn-category"
		   title="Alle Produkte dieser Kategorie ansehen">
		   Zur Kategorie
		</a>

	  	<?php if ($hasControls): ?>

		<button class="btn btn-sm btn-outline-secondary fd-vmcarousel-btn"
				type="button"
				data-bs-target="#<?php echo $carouselId; ?>"
				data-bs-slide="prev"
				aria-label="Zurück">
			<span>❮</span>
		</button>

		<button class="btn btn-sm btn-outline-secondary fd-vmcarousel-btn"
				type="button"
				data-bs-target="#<?php echo $carouselId; ?>"
				data-bs-slide="next"
				aria-label="Weiter">
			<span>❯</span>
		</button>

		<?php endif; ?>
	</div>

  <div class="carousel-inner">
    <?php foreach ($slides as $i => $slideProducts): ?>
      <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
        <div class="fd-vmcarousel-track">
          <?php foreach ($slideProducts as $product): ?>
            <div class="fd-vmcarousel-col">

              <div class="card h-100 d-flex flex-column fd-vmcard">
                <div class="spacers">

                  <?php
                    echo '<div class="ribbons">'
                      . shopFunctionsF::renderVmSubLayout('customfields', ['product' => $product, 'position' => 'toparea'])
                      . '</div>';
                  ?>

                  <div class="pr-img-handler">
                    <?php if (!empty($product->images[0])): ?>
                      <a href="<?php echo $product->link; ?>">
                        <?php echo $product->images[0]->displayMediaThumb('', false); ?>
                      </a>
                    <?php endif; ?>
                  </div>

                  <div class="action-handler">
                    <div class="action-handler-inner">

                      <h5 class="fd-vm-title">
                        <a class="fd-vm-title-link text-decoration-none" href="<?php echo $product->link; ?>">
                          <?php echo $product->product_name; ?>
                        </a>
                      </h5>

                      <?php
                        echo shopFunctionsF::renderVmSubLayout('customfields', [
                          'product'  => $product,
                          'position' => 'spezi',
                          'class'    => 'product-fields-spezi'
                        ]);
                      ?>

                      <div class="mt-2">
                        <?php echo shopFunctionsF::renderVmSubLayout('prices', ['product' => $product, 'currency' => $currency]); ?>
                      </div>

                      <?php if (!empty($show_addtocart)): ?>
                        <div class="productdetails mt-2">
                          <?php echo shopFunctionsF::renderVmSubLayout('addtocart', ['product' => $product]); ?>
                        </div>
                      <?php endif; ?>

                    </div>
                  </div>

                </div>
              </div>

            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

</div>
