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

    <?php echo HTMLHelper::_('uitab.addTab', 'fdshopConfigurationTabs', 'shipments', 'Versand'); ?>
        <div class="card mb-3">
            <div class="card-header">Versandarten</div>
            <div class="card-body">
                <p>
                    <a class="btn btn-primary" href="index.php?option=com_fdshop&view=shipment&layout=edit">
                        Hinzufügen
                    </a>
                </p>

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Versandname</th>
                            <th>Farbe</th>
                            <th>Preis</th>
                            <th>Veröffentlicht</th>
                            <th>ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($this->shipments)) : ?>
                            <?php foreach ($this->shipments as $item) : ?>
                                <tr>
                                    <td>
                                        <a href="index.php?option=com_fdshop&view=shipment&layout=edit&id=<?php echo (int) $item->id; ?>">
                                            <?php echo htmlspecialchars((string) $item->shipment_name, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) $item->shipment_color, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) $item->shipment_price, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo (int) $item->is_published; ?></td>
                                    <td><?php echo (int) $item->id; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="5">Noch keine Versandarten vorhanden.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'fdshopConfigurationTabs', 'paymentmethods', 'Bezahlsystem'); ?>
        <div class="card mb-3">
            <div class="card-header">Zahlungsarten</div>
            <div class="card-body">
                <p>
                    <a class="btn btn-primary" href="index.php?option=com_fdshop&view=paymentmethod&layout=edit">
                        Hinzufügen
                    </a>
                </p>

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Zahlungsname</th>
                            <th>Gebühr</th>
                            <th>PayPal</th>
                            <th>Veröffentlicht</th>
                            <th>ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($this->paymentmethods)) : ?>
                            <?php foreach ($this->paymentmethods as $item) : ?>
                                <tr>
                                    <td>
                                        <a href="index.php?option=com_fdshop&view=paymentmethod&layout=edit&id=<?php echo (int) $item->id; ?>">
                                            <?php echo htmlspecialchars((string) $item->payment_name, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) $item->payment_fee, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo (int) $item->paypal_enabled; ?></td>
                                    <td><?php echo (int) $item->is_published; ?></td>
                                    <td><?php echo (int) $item->id; ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php else : ?>
                            <tr>
                                <td colspan="5">Noch keine Zahlungsarten vorhanden.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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