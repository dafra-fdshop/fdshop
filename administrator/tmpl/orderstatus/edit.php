<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
?>

<form action="<?php echo Route::_('index.php?option=com_fdshop&view=orderstatus&layout=edit&id=' . (int) ($this->item->id ?? 0)); ?>" method="post" name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-12 col-xl-8">
			<div class="card mb-3">
				<div class="card-header">Bestellstatus</div>
				<div class="card-body">
					<div class="mb-3">
						<div class="fw-bold">Bezeichnung</div>
						<div><?php echo $this->escape((string) ($this->item->status_name ?? '')); ?></div>
					</div>

					<div class="mb-3">
						<div class="fw-bold">Status-Code</div>
						<div><?php echo $this->escape((string) ($this->item->status_code ?? '')); ?></div>
					</div>

					<div class="mb-3">
						<div class="fw-bold">E-Mail Verkäufer</div>
						<div><?php echo $this->escape((string) ($this->item->seller_email_mode_label ?? '')); ?></div>
						<?php if (($this->item->seller_email_mode ?? '') === 'custom' && !empty($this->item->seller_email_address)) : ?>
							<div class="small text-muted">
								<?php echo $this->escape((string) $this->item->seller_email_address); ?>
							</div>
						<?php endif; ?>
					</div>

					<div class="mb-3">
						<div class="fw-bold">E-Mail Käufer</div>
						<div><?php echo $this->escape((string) ($this->item->buyer_email_mode_label ?? '')); ?></div>
					</div>

					<div class="mb-3">
						<div class="fw-bold">Rechnung</div>
						<div><?php echo $this->escape((string) ($this->item->create_invoice_label ?? '')); ?></div>
					</div>

					<div class="mb-0">
						<div class="fw-bold">Bestand</div>
						<div><?php echo $this->escape((string) ($this->item->stock_action_label ?? '')); ?></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<input type="hidden" name="id" value="<?php echo (int) ($this->item->id ?? 0); ?>">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>