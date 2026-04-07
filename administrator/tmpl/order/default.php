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
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Produktname</th>
                        <th>SKU</th>
                        <th>Menge</th>
                        <th>Einzelpreis (Brutto)</th>
                        <th>Gesamtpreis Position</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($this->orderItems)) : ?>
                        <?php foreach ($this->orderItems as $item) : ?>
                            <tr>
                                <td><?php echo $this->escape((string) ($item->product_name ?? '')); ?></td>
                                <td><?php echo $this->escape((string) ($item->sku ?? '')); ?></td>
                                <td><?php echo $this->escape((string) ($item->quantity ?? '')); ?></td>
                                <td><?php echo number_format((float) ($item->unit_price_gross ?? 0), 2, ',', '.'); ?></td>
                                <td><?php echo number_format((float) ($item->line_total_gross ?? 0), 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" class="text-center">Keine Bestellpositionen vorhanden.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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