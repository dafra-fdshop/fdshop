<?php
defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;
?>
<div class="fdshop-search-module" data-fdshop-search-module="<?php echo (int) $module->id; ?>">
    <?php echo LayoutHelper::render('search.form', ['id' => 'module-' . (int) $module->id], JPATH_ROOT . '/components/com_fdshop/layouts'); ?>
</div>
