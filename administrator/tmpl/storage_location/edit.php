<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
?>

<form action="<?php echo Route::_('index.php?option=com_fdshop&view=storage_location&layout=edit&id=' . (int) ($this->item->id ?? 0)); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-12 col-xl-8">
            <div class="card mb-3">
                <div class="card-header">Lagerort-Stammdaten</div>
                <div class="card-body">
                    <?php echo $this->form->renderField('location_name'); ?>
                    <?php echo $this->form->renderField('code'); ?>
                    <?php echo $this->form->renderField('description'); ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card mb-3">
                <div class="card-header">Status und Sortierung</div>
                <div class="card-body">
                    <?php echo $this->form->renderField('is_active'); ?>
                    <?php echo $this->form->renderField('ordering'); ?>
                </div>
            </div>
        </div>
    </div>

    <?php echo $this->form->renderField('id'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
