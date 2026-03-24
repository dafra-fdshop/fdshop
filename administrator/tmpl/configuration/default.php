<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
?>

<form action="index.php?option=com_fdshop&view=configuration" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-12 col-xl-8">
            <div class="card mb-3">
                <div class="card-header">Konfiguration</div>
                <div class="card-body">
                    <?php echo $this->form->renderField('general_vat_rate'); ?>
                    <?php echo $this->form->renderField('image_size_default'); ?>
                    <?php echo $this->form->renderField('image_size_small'); ?>
                    <?php echo $this->form->renderField('image_size_mobile'); ?>
                    <?php echo $this->form->renderField('image_size_manufacturer'); ?>
                </div>
            </div>
        </div>
    </div>

    <?php echo $this->form->renderField('id'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>