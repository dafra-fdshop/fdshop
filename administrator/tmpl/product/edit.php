<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
?>

<form action="index.php?option=com_fdshop&view=product&layout=edit" method="post" name="adminForm" id="adminForm">
    <?php echo HTMLHelper::_('uitab.startTabSet', 'fdshopProductTabs', ['active' => 'general']); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'fdshopProductTabs', 'general', 'Allgemein'); ?>
        <div class="row">
            <div class="col-12 col-xl-6">
                <div class="card mb-3">
                    <div class="card-header">Allgemeine Felder</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('product_name'); ?>
                        <?php echo $this->form->renderField('sku'); ?>
                        <?php echo $this->form->renderField('gtin'); ?>
                        <?php echo $this->form->renderField('alias'); ?>
                        <?php echo $this->form->renderField('manufacturer_id'); ?>
                        <?php echo $this->form->renderField('category_ids'); ?>
                        <?php echo $this->form->renderField('is_active'); ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card mb-3">
                    <div class="card-header">Preis</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('active_price_net'); ?>
                        <?php echo $this->form->renderField('active_price_gross'); ?>
                        <?php echo $this->form->renderField('active_tax_rate'); ?>
                        <?php echo $this->form->renderField('currency'); ?>
                        <?php echo $this->form->renderField('purchase_price'); ?>
                        <?php echo $this->form->renderField('sale_price'); ?>
                        <?php echo $this->form->renderField('discount_price'); ?>
                        <?php echo $this->form->renderField('discount_active'); ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card mb-3">
                    <div class="card-header">Beschreibungen</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('short_description'); ?>
                        <?php echo $this->form->renderField('description'); ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card mb-3">
                    <div class="card-header">Meta</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('meta_title'); ?>
                        <?php echo $this->form->renderField('meta_keywords'); ?>
                        <?php echo $this->form->renderField('meta_description'); ?>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card mb-3">
                    <div class="card-header">Käufergruppe</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('buyer_group_ids'); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'fdshopProductTabs', 'stock', 'Lager'); ?>
        <div class="row">
            <div class="col-12 col-xl-6">
                <div class="card mb-3">
                    <div class="card-header">Lager / Status</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('stock_quantity'); ?>
                        <?php echo $this->form->renderField('reserved_quantity'); ?>
                        <?php echo $this->form->renderField('min_order_qty'); ?>
                        <?php echo $this->form->renderField('max_order_qty'); ?>
                        <?php echo $this->form->renderField('step_order_qty'); ?>
                        <?php echo $this->form->renderField('is_in_stock'); ?>
                        <?php echo $this->form->renderField('available_from'); ?>
                        <?php echo $this->form->renderField('sold_quantity'); ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card mb-3">
                    <div class="card-header">Produktinformationen</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('ordering'); ?>
                        <?php echo $this->form->renderField('publish_up'); ?>
                        <?php echo $this->form->renderField('publish_down'); ?>
                        <?php echo $this->form->renderField('access'); ?>
                        <?php echo $this->form->renderField('is_featured'); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'fdshopProductTabs', 'media', 'Medien'); ?>
        <div class="alert alert-info mb-0">
            KOMMT IN EINER SPÄTEREN VERSION
        </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'fdshopProductTabs', 'special', 'Besondere Felder'); ?>
        <div class="row">
            <div class="col-12 col-xl-4">
                <div class="card mb-3">
                    <div class="card-header">Verpackung</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('unit_type'); ?>
                        <?php echo $this->form->renderField('unit_quantity'); ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card mb-3">
                    <div class="card-header">Technische Daten</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('nem'); ?>
                        <?php echo $this->form->renderField('shot_count'); ?>
                        <?php echo $this->form->renderField('caliber'); ?>
                        <?php echo $this->form->renderField('burn_time'); ?>
                        <?php echo $this->form->renderField('rise_height'); ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card mb-3">
                    <div class="card-header">Maße</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('weight_kg'); ?>
                        <?php echo $this->form->renderField('length_cm'); ?>
                        <?php echo $this->form->renderField('width_cm'); ?>
                        <?php echo $this->form->renderField('height_cm'); ?>
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