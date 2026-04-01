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

$user = $this->getCurrentUser();

$shipmentListOrder = $this->shipmentState->get('list.ordering');
$shipmentListDirn  = $this->shipmentState->get('list.direction');

$paymentListOrder = $this->paymentState->get('list.ordering');
$paymentListDirn  = $this->paymentState->get('list.direction');

$shipmentSearchView = (object) [
	'filterForm'    => $this->shipmentFilterForm,
	'activeFilters' => $this->shipmentActiveFilters,
];

$paymentSearchView = (object) [
	'filterForm'    => $this->paymentFilterForm,
	'activeFilters' => $this->paymentActiveFilters,
];
?>

<form action="<?php echo Route::_('index.php?option=com_fdshop&view=configuration'); ?>" method="post" name="adminForm" id="adminForm">
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
					<a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_fdshop&view=shipment&layout=edit'); ?>">
						Hinzufügen
					</a>
				</p>

				<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $shipmentSearchView]); ?>

				<div class="table-responsive">
					<table class="table itemList" id="shipmentList">
						<thead>
							<tr>
								<td class="w-1 text-center">
									<?php echo HTMLHelper::_('grid.checkall'); ?>
								</td>
								<th scope="col">
									<?php echo HTMLHelper::_('searchtools.sort', 'Versandname', 'a.shipment_name', $shipmentListDirn, $shipmentListOrder); ?>
								</th>
								<th scope="col">
									<?php echo HTMLHelper::_('searchtools.sort', 'Beschreibung', 'a.shipment_description', $shipmentListDirn, $shipmentListOrder); ?>
								</th>
								<th scope="col" class="text-center">
									<?php echo HTMLHelper::_('searchtools.sort', 'Farbe', 'a.shipment_color', $shipmentListDirn, $shipmentListOrder); ?>
								</th>
								<th scope="col">
									<?php echo HTMLHelper::_('searchtools.sort', 'Gebühr', 'a.shipment_price', $shipmentListDirn, $shipmentListOrder); ?>
								</th>
								<th scope="col" class="w-1 text-center">
									<?php echo HTMLHelper::_('searchtools.sort', 'Veröffentlicht', 'a.is_published', $shipmentListDirn, $shipmentListOrder); ?>
								</th>
								<th scope="col" class="w-1 text-center">
									<?php echo HTMLHelper::_('searchtools.sort', 'ID', 'a.id', $shipmentListDirn, $shipmentListOrder); ?>
								</th>
							</tr>
						</thead>

						<tbody>
							<?php if (!empty($this->shipments)) : ?>
								<?php foreach ($this->shipments as $i => $item) : ?>
									<?php
									$editLink  = Route::_('index.php?option=com_fdshop&view=shipment&layout=edit&id=' . (int) $item->id);
									$canEdit   = $user->authorise('core.edit', 'com_fdshop');
									$canChange = $user->authorise('core.edit.state', 'com_fdshop');
									$color     = trim((string) ($item->shipment_color ?? ''));
									?>
									<tr class="row<?php echo $i % 2; ?>">
										<td class="text-center">
											<?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?>
										</td>

										<th scope="row">
											<?php if ($canEdit) : ?>
												<a href="<?php echo $editLink; ?>">
													<?php echo $this->escape((string) $item->shipment_name); ?>
												</a>
											<?php else : ?>
												<?php echo $this->escape((string) $item->shipment_name); ?>
											<?php endif; ?>
										</th>

										<td>
											<?php echo $item->shipment_description ?? ''; ?>
										</td>

										<td class="text-center">
											<?php if ($color !== '') : ?>
												<span style="display:inline-block;width:16px;height:16px;background:<?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>;"></span>
											<?php endif; ?>
										</td>

										<td>
											<?php echo $this->escape((string) $item->shipment_price); ?>
										</td>

										<td class="text-center">
											<?php echo HTMLHelper::_('jgrid.published', (int) $item->published, $i, 'shipments.', $canChange, 'cb'); ?>
										</td>

										<td class="text-center">
											<?php echo (int) $item->id; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="7" class="text-center">
										Keine Versandarten vorhanden.
									</td>
								</tr>
							<?php endif; ?>
						</tbody>

						<?php if (!empty($this->shipments)) : ?>
							<tfoot>
								<tr>
									<td colspan="7">
										<?php echo $this->shipmentPagination->getListFooter(); ?>
									</td>
								</tr>
							</tfoot>
						<?php endif; ?>
					</table>
				</div>

				<?php echo $this->shipmentFilterForm->renderControlFields(); ?>
			</div>
		</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'fdshopConfigurationTabs', 'paymentmethods', 'Bezahlsystem'); ?>
		<div class="card mb-3">
			<div class="card-header">Zahlungsarten</div>
			<div class="card-body">
				<p>
					<a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_fdshop&view=paymentmethod&layout=edit'); ?>">
						Hinzufügen
					</a>
				</p>

				<?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $paymentSearchView]); ?>

				<div class="table-responsive">
					<table class="table itemList" id="paymentmethodList">
						<thead>
							<tr>
								<td class="w-1 text-center">
									<?php echo HTMLHelper::_('grid.checkall'); ?>
								</td>
								<th scope="col">
									<?php echo HTMLHelper::_('searchtools.sort', 'Zahlungsname', 'a.payment_name', $paymentListDirn, $paymentListOrder); ?>
								</th>
								<th scope="col">
									<?php echo HTMLHelper::_('searchtools.sort', 'Beschreibung', 'a.payment_description', $paymentListDirn, $paymentListOrder); ?>
								</th>
								<th scope="col">
									<?php echo HTMLHelper::_('searchtools.sort', 'Gebühr', 'a.payment_fee', $paymentListDirn, $paymentListOrder); ?>
								</th>
								<th scope="col" class="w-1 text-center">
									<?php echo HTMLHelper::_('searchtools.sort', 'Veröffentlicht', 'a.is_published', $paymentListDirn, $paymentListOrder); ?>
								</th>
								<th scope="col" class="w-1 text-center">
									<?php echo HTMLHelper::_('searchtools.sort', 'ID', 'a.id', $paymentListDirn, $paymentListOrder); ?>
								</th>
							</tr>
						</thead>

						<tbody>
							<?php if (!empty($this->paymentmethods)) : ?>
								<?php foreach ($this->paymentmethods as $i => $item) : ?>
									<?php
									$editLink  = Route::_('index.php?option=com_fdshop&view=paymentmethod&layout=edit&id=' . (int) $item->id);
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
													<?php echo $this->escape((string) $item->payment_name); ?>
												</a>
											<?php else : ?>
												<?php echo $this->escape((string) $item->payment_name); ?>
											<?php endif; ?>
										</th>

										<td>
											<?php echo $item->payment_description ?? ''; ?>
										</td>

										<td>
											<?php echo $this->escape((string) $item->payment_fee); ?>
										</td>

										<td class="text-center">
											<?php echo HTMLHelper::_('jgrid.published', (int) $item->published, $i, 'paymentmethods.', $canChange, 'cb'); ?>
										</td>

										<td class="text-center">
											<?php echo (int) $item->id; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="6" class="text-center">
										Keine Zahlungsarten vorhanden.
									</td>
								</tr>
							<?php endif; ?>
						</tbody>

						<?php if (!empty($this->paymentmethods)) : ?>
							<tfoot>
								<tr>
									<td colspan="6">
										<?php echo $this->paymentPagination->getListFooter(); ?>
									</td>
								</tr>
							</tfoot>
						<?php endif; ?>
					</table>
				</div>

				<?php echo $this->paymentFilterForm->renderControlFields(); ?>
			</div>
		</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'fdshopConfigurationTabs', 'orderstatuses', 'Bestellstatus'); ?>
		<div class="card mb-3">
			<div class="card-header">Bestellstatus</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-striped">
						<thead>
							<tr>
								<th>Bezeichnung</th>
								<th>E-Mail Verkäufer</th>
								<th>E-Mail Käufer</th>
								<th>Rechnung</th>
								<th>Bestand</th>
							</tr>
						</thead>
						<tbody>
							<?php if (!empty($this->orderStatuses)) : ?>
								<?php foreach ($this->orderStatuses as $item) : ?>
									<tr>
										<td>
											<a href="<?php echo Route::_('index.php?option=com_fdshop&view=orderstatus&layout=edit&id=' . (int) $item->id); ?>">
												<?php echo $this->escape((string) $item->status_name); ?>
											</a>
										</td>
										<td>
											<?php echo $this->escape((string) ($item->seller_email_mode_label ?? '')); ?>
											<?php if (($item->seller_email_mode ?? '') === 'custom' && !empty($item->seller_email_address)) : ?>
												<div class="small text-muted">
													<?php echo $this->escape((string) $item->seller_email_address); ?>
												</div>
											<?php endif; ?>
										</td>
										<td>
											<?php echo $this->escape((string) ($item->buyer_email_mode_label ?? '')); ?>
										</td>
										<td>
											<?php echo $this->escape((string) ($item->create_invoice_label ?? '')); ?>
										</td>
										<td>
											<?php echo $this->escape((string) ($item->stock_action_label ?? '')); ?>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="5" class="text-center">
										Keine Bestellstatus vorhanden.
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.endTabSet'); ?>

	<?php echo $this->form->renderField('id'); ?>
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