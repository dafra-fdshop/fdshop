<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
?>

<form action="<?php echo Route::_('index.php?option=com_fdshop&view=coupon&layout=edit&id=' . (int) ($this->item->id ?? 0)); ?>" method="post" name="adminForm" id="adminForm">
    <?php echo HTMLHelper::_('uitab.startTabSet', 'fdshopCouponTabs', ['active' => 'general']); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'fdshopCouponTabs', 'general', 'Allgemein'); ?>
        <div class="row">
            <div class="col-12 col-xl-8">
                <div class="card mb-3">
                    <div class="card-header">Allgemein</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('coupon_name'); ?>
                        <?php echo $this->form->renderField('coupon_code'); ?>
                        <?php echo $this->form->renderField('alias'); ?>
                        <?php echo $this->form->renderField('description'); ?>
                        <?php echo $this->form->renderField('published'); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'fdshopCouponTabs', 'discount', 'Rabatt'); ?>
        <div class="row">
            <div class="col-12 col-xl-6">
                <div class="card mb-3">
                    <div class="card-header">Rabatt</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('discount_type'); ?>
                        <?php echo $this->form->renderField('discount_value'); ?>
                        <?php echo $this->form->renderField('minimum_order_total'); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'fdshopCouponTabs', 'restrictions', 'Einschränkungen'); ?>
        <div class="row">
            <div class="col-12 col-xl-6">
                <div class="card mb-3">
                    <div class="card-header">Benutzer und Käufergruppen</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('user_ids'); ?>
                        <?php echo $this->form->renderField('buyer_group_ids'); ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card mb-3">
                    <div class="card-header">Produkte und Kategorien</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('product_ids'); ?>
                        <?php echo $this->form->renderField('category_ids'); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'fdshopCouponTabs', 'validity', 'Gültigkeit'); ?>
        <div class="row">
            <div class="col-12 col-xl-6">
                <div class="card mb-3">
                    <div class="card-header">Gültigkeit</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('valid_from'); ?>
                        <?php echo $this->form->renderField('valid_to'); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'fdshopCouponTabs', 'usage', 'Nutzung'); ?>
        <div class="row">
            <div class="col-12 col-xl-6">
                <div class="card mb-3">
                    <div class="card-header">Nutzung</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('usage_limit_total'); ?>
                        <?php echo $this->form->renderField('usage_limit_per_user'); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

    <?php echo $this->form->renderField('id'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
