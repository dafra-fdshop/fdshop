<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$order = $this->item;
$userLink = '';
$availableProducts = $this->availableProducts ?? [];

if (!empty($order->user_id)) {
    $userLink = Route::_('index.php?option=com_users&task=user.edit&id=' . (int) $order->user_id);
}
?>

<div class="row">
    <div class="col-12 col-xl-6">
        <div class="card mb-3">
            <div class="card-header">Bestell-Kopf</div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="fw-bold">Bestellnummer</div>
                    <div><?php echo $this->escape((string) ($order->order_number ?? '')); ?></div>
                </div>

                <div class="mb-3">
                    <div class="fw-bold">Statusname</div>
                    <div><?php echo $this->escape((string) ($order->status_name ?? '')); ?></div>
                </div>

                <div class="mb-3">
                    <div class="fw-bold">Datum</div>
                    <div><?php echo !empty($order->created) ? HTMLHelper::_('date', $order->created, 'Y-m-d H:i') : ''; ?></div>
                </div>

                <div class="mb-3">
                    <div class="fw-bold">Gesamtbetrag</div>
                    <div><?php echo number_format((float) ($order->grand_total ?? 0), 2, ',', '.'); ?></div>
                </div>

                <div class="mb-0">
                    <div class="fw-bold">Währung</div>
                    <div><?php echo $this->escape((string) ($order->currency ?? '')); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card mb-3">
            <div class="card-header">Kunde</div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="fw-bold">Name</div>
                    <div>
                        <?php if ($userLink !== '') : ?>
                            <a href="<?php echo $userLink; ?>">
                                <?php echo $this->escape((string) ($order->customer_name ?? '')); ?>
                            </a>
                        <?php else : ?>
                            <?php echo $this->escape((string) ($order->customer_name ?? '')); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-0">
                    <div class="fw-bold">User-ID</div>
                    <div class="small text-muted"><?php echo (int) ($order->user_id ?? 0); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Bestellpositionen</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Produktname</th>
                        <th>SKU</th>
                        <th>Menge</th>
                        <th>Einzelpreis (Brutto)</th>
                        <th>Gesamtpreis Position</th>
                        <th>Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($this->orderItems)) : ?>
                        <?php foreach ($this->orderItems as $item) : ?>
                            <tr>
                                <td><?php echo $this->escape((string) ($item->product_name ?? '')); ?></td>
                                <td><?php echo $this->escape((string) ($item->sku ?? '')); ?></td>
                                <td>
                                    <form action="<?php echo Route::_('index.php?option=com_fdshop&task=order.updateItemQuantity'); ?>" method="post" class="d-flex gap-2 align-items-center">
                                        <input type="number" name="quantity" class="form-control" min="1" step="1" value="<?php echo (int) ($item->quantity ?? 1); ?>">
                                        <input type="hidden" name="order_id" value="<?php echo (int) ($order->id ?? 0); ?>">
                                        <input type="hidden" name="item_id" value="<?php echo (int) ($item->id ?? 0); ?>">
                                        <button type="submit" class="btn btn-outline-primary btn-sm">Speichern</button>
                                        <?php echo HTMLHelper::_('form.token'); ?>
                                    </form>
                                </td>
                                <td><?php echo number_format((float) ($item->unit_price_gross ?? 0), 2, ',', '.'); ?></td>
                                <td><?php echo number_format((float) ($item->line_total_gross ?? 0), 2, ',', '.'); ?></td>
                                <td>
                                    <form action="<?php echo Route::_('index.php?option=com_fdshop&task=order.removeItem'); ?>" method="post">
                                        <input type="hidden" name="order_id" value="<?php echo (int) ($order->id ?? 0); ?>">
                                        <input type="hidden" name="item_id" value="<?php echo (int) ($item->id ?? 0); ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Entfernen</button>
                                        <?php echo HTMLHelper::_('form.token'); ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6" class="text-center">Keine Bestellpositionen vorhanden.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Neues Produkt hinzufügen</div>
    <div class="card-body">
        <form action="<?php echo Route::_('index.php?option=com_fdshop&task=order.addItem'); ?>" method="post">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-xl-7">
                    <label for="jform_product_id" class="form-label">Produktauswahl</label>
                    <select name="product_id" id="jform_product_id" class="form-select">
                        <option value="">- Produkt wählen -</option>
                        <?php foreach ($availableProducts as $product) : ?>
                            <option value="<?php echo (int) ($product->id ?? 0); ?>">
                                <?php
                                $optionText = (string) ($product->product_name ?? '');

                                if (!empty($product->sku)) {
                                    $optionText .= ' (' . (string) $product->sku . ')';
                                }

                                echo $this->escape($optionText);
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-4 col-xl-3">
                    <label for="jform_quantity" class="form-label">Menge</label>
                    <input type="number" name="quantity" id="jform_quantity" class="form-control" min="1" step="1" value="1">
                </div>

                <div class="col-12 col-md-8 col-xl-2">
                    <button type="submit" class="btn btn-success w-100">Hinzufügen</button>
                </div>
            </div>

            <input type="hidden" name="order_id" value="<?php echo (int) ($order->id ?? 0); ?>">
            <?php echo HTMLHelper::_('form.token'); ?>
        </form>

        <?php if (empty($availableProducts)) : ?>
            <div class="text-muted small mt-3">Keine Produktauswahl verfügbar.</div>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Summenblock</div>
    <div class="card-body">
        <div class="fw-bold">Gesamtbetrag</div>
        <div><?php echo number_format((float) ($order->grand_total ?? 0), 2, ',', '.'); ?></div>
    </div>
</div>

<?php if (!empty($this->statusHistory)) : ?>
    <div class="card mb-3">
        <div class="card-header">Status-Historie</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Alter Status</th>
                            <th>Neuer Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->statusHistory as $entry) : ?>
                            <tr>
                                <td><?php echo !empty($entry->changed_at) ? HTMLHelper::_('date', $entry->changed_at, 'Y-m-d H:i') : ''; ?></td>
                                <td><?php echo $this->escape((string) ($entry->old_status_name ?? '')); ?></td>
                                <td><?php echo $this->escape((string) ($entry->new_status_name ?? '')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($this->orderHistory)) : ?>
    <div class="card mb-3">
        <div class="card-header">Allgemeine Historie</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Titel</th>
                            <th>Text</th>
                            <th>Datum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->orderHistory as $entry) : ?>
                            <tr>
                                <td><?php echo $this->escape((string) ($entry->event_title ?? '')); ?></td>
                                <td><?php echo $this->escape((string) ($entry->event_text ?? '')); ?></td>
                                <td><?php echo !empty($entry->created) ? HTMLHelper::_('date', $entry->created, 'Y-m-d H:i') : ''; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>