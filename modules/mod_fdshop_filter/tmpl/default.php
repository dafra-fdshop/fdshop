<?php

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

$suffix = 'module-' . (int) $module->id;
?>
<div class="fdshop-filter-module" data-fdshop-filter-module aria-label="FDShop Produktfilter">
    <?php echo LayoutHelper::render('filter.panel', $filterData + ['suffix' => $suffix], JPATH_ROOT . '/components/com_fdshop/layouts'); ?>
</div>
