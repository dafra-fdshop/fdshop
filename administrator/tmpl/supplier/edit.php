<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
?>

<form action="<?php echo Route::_('index.php?option=com_fdshop&view=supplier&layout=edit&id=' . (int) ($this->item->id ?? 0)); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-12 col-xl-8">
            <div class="card mb-3">
                <div class="card-header">Lieferanten-Stammdaten</div>
                <div class="card-body">
                    <?php echo $this->form->renderField('supplier_name'); ?>
                    <?php echo $this->form->renderField('alias'); ?>
                    <?php echo $this->form->renderField('contact_name'); ?>
                    <?php echo $this->form->renderField('email'); ?>
                    <?php echo $this->form->renderField('phone'); ?>
                    <?php echo $this->form->renderField('website'); ?>
                    <?php echo $this->form->renderField('customer_number'); ?>
                    <?php echo $this->form->renderField('note'); ?>
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
