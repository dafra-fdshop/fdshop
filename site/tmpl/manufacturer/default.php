<?php
defined('_JEXEC') or die;
?>
<main class="fdshop-manufacturer" data-manufacturer-id="<?php echo (int) $this->item->id; ?>">
    <h1><?php echo $this->escape((string) $this->item->manufacturer_name); ?></h1>
    <div class="fdshop-manufacturer__content"><p>Weitere Informationen und Produkte dieses Herstellers folgen.</p></div>
</main>
