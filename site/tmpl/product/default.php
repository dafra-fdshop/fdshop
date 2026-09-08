<?php

defined('_JEXEC') or die;
?>
<main class="fdshop-product-placeholder">
    <h1><?php echo $this->escape((string) $this->item->product_name); ?></h1>
    <?php if (trim((string) $this->item->short_description) !== '') : ?><p><?php echo $this->escape((string) $this->item->short_description); ?></p><?php endif; ?>
    <p>Die vollständige Produktdetailansicht folgt in einem eigenen Entwicklungspaket.</p>
    <?php if ($this->categoryUrl !== '') : ?><p><a class="btn btn-primary" href="<?php echo $this->escape($this->categoryUrl); ?>">Zurück zur Kategorie</a></p><?php endif; ?>
</main>
