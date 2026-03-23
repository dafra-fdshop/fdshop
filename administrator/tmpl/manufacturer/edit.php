<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
?>

<form action="index.php?option=com_fdshop&view=manufacturer&layout=edit" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-12 col-xl-8">
            <div class="card mb-3">
                <div class="card-header">Hersteller</div>
                <div class="card-body">
                    <?php echo $this->form->renderField('manufacturer_name'); ?>
                    <?php echo $this->form->renderField('alias'); ?>
                    <?php echo $this->form->renderField('description'); ?>
                    <?php echo $this->form->renderField('is_active'); ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card mb-3">
                <div class="card-header">Meta-Informationen</div>
                <div class="card-body">
                    <?php echo $this->form->renderField('meta_title'); ?>
                    <?php echo $this->form->renderField('meta_keywords'); ?>
                    <?php echo $this->form->renderField('meta_description'); ?>
                </div>
            </div>
        </div>
    </div>

    <?php echo $this->form->renderField('id'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>