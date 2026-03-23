<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;
?>

<form action="index.php?option=com_fdshop&view=category&layout=edit" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-12 col-lg-8">
            <?php echo $this->form->renderField('category_name'); ?>
            <?php echo $this->form->renderField('alias'); ?>
            <?php echo $this->form->renderField('parent_id'); ?>
            <?php echo $this->form->renderField('description'); ?>
            <?php echo $this->form->renderField('is_active'); ?>
        </div>
    </div>

    <?php echo $this->form->renderField('id'); ?>
    <input type="hidden" name="task" value="">
    <?php echo \Joomla\CMS\HTML\HTMLHelper::_('form.token'); ?>
</form>