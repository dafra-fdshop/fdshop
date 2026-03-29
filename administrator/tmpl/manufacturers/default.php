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

<form action="<?php echo Route::_('index.php?option=com_fdshop&view=manufacturers'); ?>" method="post" name="adminForm" id="adminForm">
	<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

	<div class="table-responsive">
		<table class="table itemList" id="manufacturerList">
			<thead>
				<tr>
					<td class="w-1 text-center">
						<?php echo HTMLHelper::_('grid.checkall'); ?>
					</td>
					<th scope="col">
						<?php echo HTMLHelper::_('searchtools.sort', 'Name', 'a.manufacturer_name', $listDirn, $listOrder); ?>
					</th>
					<th scope="col">
						<?php echo HTMLHelper::_('searchtools.sort', 'Beschreibung', 'a.description', $listDirn, $listOrder); ?>
					</th>
					<th scope="col" class="w-1 text-center">
						<?php echo HTMLHelper::_('searchtools.sort', 'Veröffentlicht', 'a.is_active', $listDirn, $listOrder); ?>
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
						$editLink  = Route::_('index.php?option=com_fdshop&view=manufacturer&layout=edit&id=' . (int) $item->id);
						$canEdit   = $user->authorise('core.edit', 'com_fdshop');
						$canChange = $user->authorise('core.edit.state', 'com_fdshop');
						?>
						<tr class="row<?php echo $i % 2; ?>">
							<td class="text-center">
								<?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?>
							</td>

							<th scope="row">
								<?php if ($canEdit) : ?>
									<a href="<?php echo $editLink; ?>">
										<?php echo $this->escape((string) $item->manufacturer_name); ?>
									</a>
								<?php else : ?>
									<?php echo $this->escape((string) $item->manufacturer_name); ?>
								<?php endif; ?>

								<?php if (!empty($item->alias)) : ?>
									<div class="small text-muted">
										<?php echo $this->escape((string) $item->alias); ?>
									</div>
								<?php endif; ?>
							</th>

							<td>
								<?php echo $item->description ?? ''; ?>
							</td>

							<td class="text-center">
								<?php echo HTMLHelper::_('jgrid.published', (int) $item->published, $i, 'manufacturers.', $canChange, 'cb'); ?>
							</td>

							<td class="text-center">
								<?php echo (int) $item->id; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="5" class="text-center">
							Keine Hersteller vorhanden.
						</td>
					</tr>
				<?php endif; ?>
			</tbody>

			<?php if (!empty($this->items)) : ?>
				<tfoot>
					<tr>
						<td colspan="5">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
				</tfoot>
			<?php endif; ?>
		</table>
	</div>

	<?php echo $this->filterForm->renderControlFields(); ?>
</form>