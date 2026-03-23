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

                        <div class="mb-3">
                            <label class="form-label">Käufergruppe</label>
                            <div class="form-text">KOMMT IN EINER SPÄTEREN VERSION</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card mb-3">
                    <div class="card-header">Preisangabe</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('active_price_net'); ?>
                        <?php echo $this->form->renderField('active_price_gross'); ?>
                        <?php echo $this->form->renderField('active_tax_rate'); ?>
                        <?php echo $this->form->renderField('currency'); ?>
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
                    <div class="card-header">Meta-Informationen</div>
                    <div class="card-body">
                        <div class="form-text">Aktuell keine vorhandenen Meta-Felder in dieser Version.</div>
                    </div>
                </div>
            </div>
        </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'fdshopProductTabs', 'stock', 'Lager'); ?>
        <div class="row">
            <div class="col-12 col-xl-6">
                <div class="card mb-3">
                    <div class="card-header">Lagerinformationen</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('stock_quantity'); ?>
                        <?php echo $this->form->renderField('reserved_quantity'); ?>
                        <?php echo $this->form->renderField('min_order_qty'); ?>
                        <?php echo $this->form->renderField('max_order_qty'); ?>
                        <?php echo $this->form->renderField('step_order_qty'); ?>
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
        <div class="alert alert-info mb-0">
            KOMMT IN EINER SPÄTEREN VERSION
        </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

    <?php echo $this->form->renderField('id'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>