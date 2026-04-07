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

$user      = $this->getCurrentUser();
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
?>

<form action="<?php echo Route::_('index.php?option=com_fdshop&view=orders'); ?>" method="post" name="adminForm" id="adminForm">
	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

	<div class="table-responsive">
		<table class="table itemList" id="orderList">
			<thead>
				<tr>
					<td class="w-1 text-center">
						<?php echo HTMLHelper::_('grid.checkall'); ?>
					</td>
					<th scope="col">
						<?php echo HTMLHelper::_('searchtools.sort', 'Bestellnummer', 'a.order_number', $listDirn, $listOrder); ?>
					</th>
					<th scope="col">
						<?php echo HTMLHelper::_('searchtools.sort', 'Kunde', 'u.name', $listDirn, $listOrder); ?>
					</th>
					<th scope="col">
						<?php echo HTMLHelper::_('searchtools.sort', 'Status', 'os.status_name', $listDirn, $listOrder); ?>
					</th>
					<th scope="col">
						<?php echo HTMLHelper::_('searchtools.sort', 'Gesamtbetrag', 'a.grand_total', $listDirn, $listOrder); ?>
					</th>
					<th scope="col">
						<?php echo HTMLHelper::_('searchtools.sort', 'Datum', 'a.created', $listDirn, $listOrder); ?>
					</th>
					<th scope="col" class="w-1 text-center">
						<?php echo HTMLHelper::_('searchtools.sort', 'ID', 'a.id', $listDirn, $listOrder); ?>
					</th>
				</tr>
			</thead>

			<tbody>
				<?php if (!empty($this->items)) : ?>
					<?php foreach ($this->items as $i => $item) : ?>
						<?php
						$orderLink    = Route::_('index.php?option=com_fdshop&view=order&id=' . (int) $item->id);
						$customerLink = '';
						$amount       = number_format((float) ($item->grand_total ?? 0), 2, ',', '.');

						if (!empty($item->currency)) {
							$amount .= ' ' . $this->escape((string) $item->currency);
						}

						if (!empty($item->user_id)) {
							$customerLink = Route::_('index.php?option=com_users&task=user.edit&id=' . (int) $item->user_id);
						}
						?>
						<tr class="row<?php echo $i % 2; ?>">
							<td class="text-center">
								<?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?>
							</td>

							<th scope="row">
								<a href="<?php echo $orderLink; ?>">
									<?php echo $this->escape((string) $item->order_number); ?>
								</a>
							</th>

							<td>
								<?php if ($customerLink !== '') : ?>
									<a href="<?php echo $customerLink; ?>">
										<?php echo $this->escape((string) ($item->customer_name ?? '')); ?>
									</a>
								<?php else : ?>
									<?php echo $this->escape((string) ($item->customer_name ?? '')); ?>
								<?php endif; ?>
							</td>

							<td>
								<?php echo $this->escape((string) ($item->status_name ?? '')); ?>
							</td>

							<td>
								<?php echo $amount; ?>
							</td>

							<td>
								<?php echo HTMLHelper::_('date', $item->created, 'Y-m-d H:i'); ?>
							</td>

							<td class="text-center">
								<?php echo (int) $item->id; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="7" class="text-center">
							Keine Bestellungen vorhanden.
						</td>
					</tr>
				<?php endif; ?>
			</tbody>

			<?php if (!empty($this->items)) : ?>
				<tfoot>
					<tr>
						<td colspan="7">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
				</tfoot>
			<?php endif; ?>
		</table>
	</div>

	<?php echo $this->filterForm->renderControlFields(); ?>
</form>