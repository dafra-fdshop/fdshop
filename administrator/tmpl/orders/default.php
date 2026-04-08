<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('bootstrap.tooltip');

$user          = $this->getCurrentUser();
$listOrder     = $this->state->get('list.ordering');
$listDirn      = $this->state->get('list.direction');
$statusOptions = $this->statusOptions ?? [];
?>

<form action="<?php echo Route::_('index.php?option=com_fdshop&view=orders'); ?>" method="post" name="adminForm" id="adminForm">
	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
	
	<div class="row mb-3">
		<div class="col-12 col-md-4 col-xl-3">
			<label for="bulk_order_status_id" class="form-label">Neuer Bestellstatus für markierte</label>
			<select name="order_status_id" id="bulk_order_status_id" class="form-select">
				<option value="0">— Bitte wählen —</option>
				<?php foreach ($statusOptions as $option) : ?>
					<?php
					$optionValue = (int) ($option->value ?? 0);

					if ($optionValue <= 0) {
						continue;
					}
					?>
					<option value="<?php echo $optionValue; ?>">
						<?php echo $this->escape((string) ($option->text ?? '')); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>

	<div class="table-responsive">
		<table class="table itemList" id="orderList">
			<thead>
				<tr>
					<td class="w-1 text-center">
						<?php echo HTMLHelper::_('grid.checkall'); ?>
					</td>
					<th scope="col">
						<?php echo HTMLHelper::_('searchtools.sort', 'Bestellnummer/Rechnungsnummer', 'a.order_number', $listDirn, $listOrder); ?>
					</th>
					<th scope="col">
						<?php echo HTMLHelper::_('searchtools.sort', 'Bestellstatus', 'os.status_name', $listDirn, $listOrder); ?>
					</th>
					<th scope="col">
						<?php echo HTMLHelper::_('searchtools.sort', 'Kunde/Email', 'u.name', $listDirn, $listOrder); ?>
					</th>
					<th scope="col">
						<?php echo HTMLHelper::_('searchtools.sort', 'Zahlungsart', 'pm.payment_name', $listDirn, $listOrder); ?>
					</th>
					<th scope="col">
						<?php echo HTMLHelper::_('searchtools.sort', 'Versandart', 's.shipment_name', $listDirn, $listOrder); ?>
					</th>
					<th scope="col">
						<?php echo HTMLHelper::_('searchtools.sort', 'Datum (bestellt)', 'a.created', $listDirn, $listOrder); ?>
					</th>
					<th scope="col">
						<?php echo HTMLHelper::_('searchtools.sort', 'Datum (geändert)', 'a.modified', $listDirn, $listOrder); ?>
					</th>
					<th scope="col" class="text-center">
						Bezahlt
					</th>
					<th scope="col">
						<?php echo HTMLHelper::_('searchtools.sort', 'Betrag', 'a.grand_total', $listDirn, $listOrder); ?>
					</th>
				</tr>
			</thead>

			<tbody>
				<?php if (!empty($this->items)) : ?>
					<?php foreach ($this->items as $i => $item) : ?>
						<?php
						$orderLink       = Route::_('index.php?option=com_fdshop&view=order&id=' . (int) $item->id);
						$customerLink    = '';
						$amount          = number_format((float) ($item->grand_total ?? 0), 2, ',', '.');
						$shipmentStyle   = '';
						$currentStatusId = (int) ($item->order_status_id ?? 0);

						if (!empty($item->currency)) {
							$amount .= ' ' . $this->escape((string) $item->currency);
						}

						if (!empty($item->user_id)) {
							$customerLink = Route::_('index.php?option=com_users&task=user.edit&id=' . (int) $item->user_id);
						}

						if (!empty($item->shipment_color)) {
							$shipmentStyle = 'background-color: ' . $this->escape((string) $item->shipment_color) . ';';
						}
						?>
						<tr class="row<?php echo $i % 2; ?>">
							<td class="text-center">
								<?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?>
							</td>

							<th scope="row">
								<a href="<?php echo $orderLink; ?>">
									<?php echo $this->escape((string) ($item->order_number ?? '')); ?>
								</a>
								<div class="small text-muted">—</div>
							</th>

							<td>
								<select class="form-select form-select-sm" disabled>
									<?php foreach ($this->statusOptions as $option) : ?>
										<?php
										$optionValue = (int) ($option->value ?? 0);

										if ($optionValue <= 0) {
											continue;
										}
										?>
										<option value="<?php echo $optionValue; ?>" <?php echo $optionValue === $currentStatusId ? 'selected' : ''; ?>>
											<?php echo $this->escape((string) ($option->text ?? '')); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>

							<td>
								<div>
									<?php if ($customerLink !== '') : ?>
										<a href="<?php echo $customerLink; ?>">
											<?php echo $this->escape((string) ($item->customer_name ?? '')); ?>
										</a>
									<?php else : ?>
										<?php echo $this->escape((string) ($item->customer_name ?? '')); ?>
									<?php endif; ?>
								</div>
								<div class="small text-muted">
									<?php echo $this->escape((string) ($item->customer_email ?? '')); ?>
								</div>
							</td>

							<td>
								<?php echo $this->escape((string) ($item->payment_name ?? '')); ?>
							</td>

							<td>
								<span class="d-inline-block px-2 py-1 rounded" style="<?php echo $shipmentStyle; ?>">
									<?php echo $this->escape((string) ($item->shipment_name ?? '')); ?>
								</span>
							</td>

							<td>
								<?php echo !empty($item->created) ? HTMLHelper::_('date', $item->created, 'Y-m-d H:i') : ''; ?>
							</td>

							<td>
								<?php echo !empty($item->modified) ? HTMLHelper::_('date', $item->modified, 'Y-m-d H:i') : ''; ?>
							</td>

							<td class="text-center">
								<?php echo !empty($item->is_paid) ? '✔' : '✘'; ?>
							</td>

							<td>
								<?php echo $amount; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="10" class="text-center">
							Keine Bestellungen vorhanden.
						</td>
					</tr>
				<?php endif; ?>
			</tbody>

			<?php if (!empty($this->items)) : ?>
				<tfoot>
					<tr>
						<td colspan="10">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
				</tfoot>
			<?php endif; ?>
		</table>
	</div>

	<input type="hidden" name="confirm_trash" id="confirm_trash" value="0">

	<?php echo $this->filterForm->renderControlFields(); ?>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var adminForm = document.getElementById('adminForm');
    var confirmTrashField = document.getElementById('confirm_trash');

    if (!adminForm || !confirmTrashField || typeof Joomla === 'undefined') {
        return;
    }

    var originalSubmitbutton = Joomla.submitbutton;

    Joomla.submitbutton = function (task) {
        if (task === 'orders.trash') {
            var confirmed = window.confirm('ACHTUNG sind sie sicher, dass sie die Bestellung in den Papierkorb verschieben wollen!');

            if (!confirmed) {
                return false;
            }

            confirmTrashField.value = '1';
            Joomla.submitform(task, adminForm);

            return true;
        }

        confirmTrashField.value = '0';

        if (typeof originalSubmitbutton === 'function') {
            return originalSubmitbutton(task);
        }

        Joomla.submitform(task, adminForm);

        return true;
    };
});
</script>