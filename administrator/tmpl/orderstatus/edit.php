<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$buyerEmailMode = match ((string) ($this->item->buyer_email_mode ?? '')) {
    'account' => 'Ja',
    'none'    => 'Nein',
    default   => (string) ($this->item->buyer_email_mode ?? ''),
};
?>

<form action="<?php echo Route::_('index.php?option=com_fdshop&view=orderstatus&layout=edit&id=' . (int) ($this->item->id ?? 0)); ?>" method="post" name="adminForm" id="adminForm">
	<div class="row">
		<div class="col-12 col-xl-8">
			<div class="card mb-3">
				<div class="card-header">Bestellstatus</div>
				<div class="card-body">
					<div class="mb-3">
						<div class="fw-bold">Status-Code</div>
						<div><?php echo $this->escape((string) ($this->item->status_code ?? '')); ?></div>
					</div>

					<div class="mb-3">
						<div class="fw-bold">E-Mail Käufer</div>
						<div><?php echo $this->escape($buyerEmailMode); ?></div>
					</div>

					<?php echo $this->form->renderField('status_name'); ?>
					<?php echo $this->form->renderField('seller_email_mode'); ?>
					<?php echo $this->form->renderField('seller_email_address'); ?>
					<?php echo $this->form->renderField('create_invoice'); ?>
					<?php echo $this->form->renderField('stock_action'); ?>
				</div>
			</div>
		</div>
	</div>

	<?php echo $this->form->renderField('id'); ?>
	<input type="hidden" name="task" value="">
	<?php echo HTMLHelper::_('form.token'); ?>
</form>