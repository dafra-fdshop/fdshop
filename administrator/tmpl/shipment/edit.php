<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
?>

<form action="index.php?option=com_fdshop&view=shipment&layout=edit" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-12 col-xl-8">
            <div class="card mb-3">
                <div class="card-header">Versandart</div>
                <div class="card-body">
                    <?php echo $this->form->renderField('shipment_name'); ?>
                    <?php echo $this->form->renderField('shipment_description'); ?>
                    <?php echo $this->form->renderField('shipment_color'); ?>
                    <?php echo $this->form->renderField('shipment_price'); ?>
                    <?php echo $this->form->renderField('is_published'); ?>
                </div>
            </div>
        </div>
    </div>

    <?php echo $this->form->renderField('id'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>