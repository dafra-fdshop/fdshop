<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
?>

<form action="index.php?option=com_fdshop&view=configuration" method="post" name="adminForm" id="adminForm">
    <?php echo HTMLHelper::_('uitab.startTabSet', 'fdshopConfigurationTabs', ['active' => 'general']); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'fdshopConfigurationTabs', 'general', 'Allgemein'); ?>
        <div class="row">
            <div class="col-12 col-xl-8">
                <div class="card mb-3">
                    <div class="card-header">Allgemein</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('general_vat_rate'); ?>
                        <?php echo $this->form->renderField('show_terms_checkbox'); ?>
                        <?php echo $this->form->renderField('require_terms_checkbox'); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'fdshopConfigurationTabs', 'images', 'Bilder'); ?>
        <div class="row">
            <div class="col-12 col-xl-8">
                <div class="card mb-3">
                    <div class="card-header">Bilder</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('image_size_default'); ?>
                        <?php echo $this->form->renderField('image_size_small'); ?>
                        <?php echo $this->form->renderField('image_size_mobile'); ?>
                        <?php echo $this->form->renderField('image_size_manufacturer'); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'fdshopConfigurationTabs', 'shipment', 'Versand'); ?>
        <div class="row">
            <div class="col-12 col-xl-8">
                <div class="card mb-3">
                    <div class="card-header">Versand</div>
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
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'fdshopConfigurationTabs', 'payment', 'Bezahlsystem'); ?>
        <div class="row">
            <div class="col-12 col-xl-8">
                <div class="card mb-3">
                    <div class="card-header">Bezahlsystem</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('payment_name'); ?>
                        <?php echo $this->form->renderField('payment_description'); ?>
                        <?php echo $this->form->renderField('payment_fee'); ?>
                        <?php echo $this->form->renderField('paypal_enabled'); ?>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const showTermsNo = document.querySelector('input[name="jform[show_terms_checkbox]"][value="0"]');
    const showTermsYes = document.querySelector('input[name="jform[show_terms_checkbox]"][value="1"]');
    const requireTermsYes = document.querySelector('input[name="jform[require_terms_checkbox]"][value="1"]');
    const requireTermsNo = document.querySelector('input[name="jform[require_terms_checkbox]"][value="0"]');
    const requireTermsInputs = document.querySelectorAll('input[name="jform[require_terms_checkbox]"]');

    function syncRequireTermsState() {
        const enabled = showTermsYes && showTermsYes.checked;

        if (!enabled && requireTermsNo) {
            requireTermsNo.checked = true;
        }

        requireTermsInputs.forEach(function (input) {
            input.disabled = !enabled;
        });
    }

    if (showTermsNo) {
        showTermsNo.addEventListener('change', syncRequireTermsState);
    }

    if (showTermsYes) {
        showTermsYes.addEventListener('change', syncRequireTermsState);
    }

    syncRequireTermsState();
});
</script>