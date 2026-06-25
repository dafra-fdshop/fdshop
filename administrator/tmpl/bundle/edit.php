<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

HTMLHelper::_('script', 'com_fdshop/admin-bundle.js', ['version' => 'auto', 'relative' => true]);

$token = Session::getFormToken();
$lookupUrl = Route::_('index.php?option=com_fdshop&task=bundle.lookupProduct&format=json', false);
$products = $this->bundleProducts ?? [];
$discountRules = $this->discountRules ?? [];
?>

<form action="<?php echo Route::_('index.php?option=com_fdshop&view=bundle&layout=edit&id=' . (int) ($this->item->id ?? 0)); ?>" method="post" name="adminForm" id="adminForm">
    <?php echo HTMLHelper::_('uitab.startTabSet', 'fdshopBundleTabs', ['active' => 'basic']); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'fdshopBundleTabs', 'basic', 'Grunddaten'); ?>
        <div class="row">
            <div class="col-12 col-xl-8">
                <div class="card mb-3">
                    <div class="card-header">Grunddaten</div>
                    <div class="card-body">
                        <?php echo $this->form->renderField('bundle_number'); ?>
                        <?php echo $this->form->renderField('bundle_name'); ?>
                        <?php echo $this->form->renderField('alias'); ?>
                        <?php echo $this->form->renderField('description'); ?>
                        <?php echo $this->form->renderField('is_active'); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'fdshopBundleTabs', 'products', 'Produkte'); ?>
        <div class="card mb-3" id="fdshop-bundle-products" data-lookup-url="<?php echo $this->escape($lookupUrl); ?>" data-token="<?php echo $this->escape($token); ?>">
            <div class="card-header">Produkte</div>
            <div class="card-body">
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-12 col-md-6 col-xl-4">
                        <label for="bundle-product-sku" class="form-label">SKU / Artikelnummer</label>
                        <input type="text" id="bundle-product-sku" class="form-control" autocomplete="off">
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="button" class="btn btn-secondary" id="bundle-product-add">
                            Produkt hinzufügen
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table" id="bundle-product-table">
                        <thead>
                            <tr>
                                <th scope="col">Produktname</th>
                                <th scope="col">SKU</th>
                                <th scope="col">Einzelpreis</th>
                                <th scope="col" class="w-1 text-center">Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product) : ?>
                                <tr data-product-id="<?php echo (int) $product->product_id; ?>">
                                    <td>
                                        <?php echo $this->escape((string) ($product->product_name ?? '')); ?>
                                        <input type="hidden" name="jform[product_ids][]" value="<?php echo (int) $product->product_id; ?>">
                                    </td>
                                    <td><?php echo $this->escape((string) ($product->sku ?? '')); ?></td>
                                    <td>—</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger bundle-product-remove">Entfernen</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'fdshopBundleTabs', 'discounts', 'Rabattstufen'); ?>
        <div class="card mb-3" id="fdshop-bundle-discounts">
            <div class="card-header">Rabattstufen</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="bundle-discount-table">
                        <thead>
                            <tr>
                                <th scope="col">Mindestanzahl Produkte</th>
                                <th scope="col">Rabatt %</th>
                                <th scope="col" class="w-1 text-center">Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($discountRules as $index => $rule) : ?>
                                <tr>
                                    <td>
                                        <input
                                            type="number"
                                            name="jform[discount_rules][<?php echo (int) $index; ?>][min_quantity]"
                                            value="<?php echo $this->escape((string) ($rule->min_quantity ?? 1)); ?>"
                                            min="1"
                                            step="1"
                                            class="form-control"
                                        >
                                        <input
                                            type="hidden"
                                            name="jform[discount_rules][<?php echo (int) $index; ?>][ordering]"
                                            value="<?php echo (int) ($rule->ordering ?? ($index + 1)); ?>"
                                        >
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            name="jform[discount_rules][<?php echo (int) $index; ?>][discount_percent]"
                                            value="<?php echo $this->escape((string) ($rule->discount_percent ?? 0)); ?>"
                                            min="0"
                                            step="0.01"
                                            class="form-control"
                                        >
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger bundle-discount-remove">Entfernen</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <button type="button" class="btn btn-secondary" id="bundle-discount-add">
                    Rabattstufe hinzufügen
                </button>
            </div>
        </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

    <?php echo $this->form->renderField('id'); ?>
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
