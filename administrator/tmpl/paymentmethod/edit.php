<?php
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$this->getDocument()->getWebAssetManager()->useScript('com_fdshop.admin-paymentmethod');
$provider = (string) ($this->item->provider ?? '') ?: ((int) ($this->item->paypal_enabled ?? 0) === 1 ? 'paypal' : '');
$showConfiguration = $provider === 'paypal' && $this->canManageCredentials;
$sourceLabel = static fn (string $source): string => ['environment' => 'Environment', 'file' => 'Externe Secret-Datei', 'none' => 'Nicht konfiguriert'][$source] ?? 'Nicht konfiguriert';
$disabled = fn (string $mode, string $name): bool => !$this->paypalAdmin['store']['writable'] || $this->paypalAdmin['modes'][$mode][$name]['source'] === 'environment';
?>
<form action="<?php echo Route::_('index.php?option=com_fdshop&view=paymentmethod&layout=edit'); ?>" method="post" name="adminForm" id="adminForm">
<?php echo HTMLHelper::_('uitab.startTabSet', 'fdshopPaymentmethodTabs', ['active' => 'general']); ?>
<?php echo HTMLHelper::_('uitab.addTab', 'fdshopPaymentmethodTabs', 'general', 'Zahlungsart'); ?>
<div class="row"><div class="col-12 col-xl-8"><div class="card mb-3"><div class="card-header">Allgemein</div><div class="card-body">
<?php echo $this->form->renderField('payment_name'); ?><?php echo $this->form->renderField('payment_description'); ?><?php echo $this->form->renderField('payment_fee'); ?><?php echo $this->form->renderField('provider'); ?><?php echo $this->form->renderField('published'); ?>
<p class="form-text">Nach Auswahl und Speicherung von PayPal steht der Tab <strong>Konfiguration</strong> bereit.</p>
</div></div></div></div>
<?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php if ($showConfiguration) : ?>
<?php echo HTMLHelper::_('uitab.addTab', 'fdshopPaymentmethodTabs', 'paypal-configuration', 'Konfiguration'); ?>
<div data-paypal-configuration data-current-mode="<?php echo $this->escape($this->paypalAdmin['mode']); ?>">
<div class="alert <?php echo $this->paypalAdmin['mode'] === 'live' ? 'alert-danger' : 'alert-warning'; ?>"><strong>PAYPAL <?php echo strtoupper($this->escape($this->paypalAdmin['mode'])); ?> AKTIV</strong><?php if ($this->paypalAdmin['mode'] === 'live') : ?><br>Echte Transaktionen sind aktiv. Testkäufe nicht mit Live-Zugangsdaten ausführen.<?php endif; ?></div>
<?php if (!$this->paypalAdmin['store']['writable']) : ?><div class="alert alert-danger"><strong>Nur-Lese-Modus:</strong> Der externe FDShop-Secret-Store kann von Joomla nicht beschrieben werden. Die PayPal-Zugangsdaten müssen serverseitig gepflegt werden.<br>Pfad: <code><?php echo $this->escape($this->paypalAdmin['store']['path']); ?></code><br>Optional kann der Pfad serverseitig mit <code>FDSHOP_SECRET_FILE</code> festgelegt werden.</div><?php endif; ?>
<?php if ($this->paypalAdmin['mode_source'] === 'environment') : ?><div class="alert alert-info">Der Betriebsmodus wird durch <code>FDSHOP_PAYPAL_MODE</code> serverseitig vorgegeben und kann hier nicht überschrieben werden.</div><?php endif; ?>
<div class="row"><div class="col-12 col-xl-8"><div class="card mb-3"><div class="card-header">Betriebsmodus &amp; Payment</div><div class="card-body">
<?php if ($this->paypalAdmin['mode_source'] === 'environment' || !$this->paypalAdmin['store']['writable']) $this->form->setFieldAttribute('paypal_mode', 'disabled', 'true'); ?>
<?php if (!$this->paypalAdmin['store']['writable']) $this->form->setFieldAttribute('paypal_reservation_minutes', 'disabled', 'true'); ?>
<?php echo $this->form->renderField('paypal_mode'); ?><?php echo $this->form->renderField('paypal_live_confirmation'); ?><?php echo $this->form->renderField('paypal_reservation_minutes'); ?>
<p class="form-text">Zulässig: 5 bis 30 Minuten. Die Einstellung gilt providerweit für alle PayPal-Zahlungsarten.</p>
</div></div></div></div>
<div class="row">
<?php foreach (['sandbox' => 'Sandbox', 'live' => 'Live'] as $mode => $title) : ?>
<div class="col-12 col-xl-6"><div class="card mb-3"><div class="card-header"><?php echo $title; ?></div><div class="card-body"><ul class="list-unstyled">
<?php foreach (['client_id' => 'Client-ID', 'client_secret' => 'Client Secret', 'webhook_id' => 'Webhook-ID'] as $name => $label) : $state = $this->paypalAdmin['modes'][$mode][$name]; ?><li><strong><?php echo $label; ?>:</strong> <?php echo $state['configured'] ? 'Ja' : 'Nein'; ?> · Quelle: <?php echo $sourceLabel($state['source']); ?></li><?php endforeach; ?>
</ul>
<?php foreach (['client_id', 'client_secret', 'webhook_id'] as $name) if ($disabled($mode, $name)) $this->form->setFieldAttribute($mode . '_' . $name, 'disabled', 'true'); ?>
<?php echo $this->form->renderField($mode . '_client_id'); ?><?php echo $this->form->renderField($mode . '_client_secret'); ?><p class="form-text">Konfiguriert: <?php echo $this->paypalAdmin['modes'][$mode]['client_secret']['configured'] ? 'Ja' : 'Nein'; ?>. Leer lassen erhält das vorhandene Secret. Ein bestehendes Secret wird niemals angezeigt.</p><?php echo $this->form->renderField($mode . '_webhook_id'); ?>
</div></div></div>
<?php endforeach; ?>
</div>
<div class="card mb-3"><div class="card-header">Webhook einrichten</div><div class="card-body"><label class="form-label" for="fdshop-paypal-webhook-url">Webhook URL</label><div class="input-group"><input id="fdshop-paypal-webhook-url" class="form-control" readonly value="<?php echo $this->escape($this->paypalWebhookUrl); ?>"><button class="btn btn-outline-secondary" type="button" data-copy-paypal-webhook>URL kopieren</button></div><p class="mt-3 mb-1">PayPal Developer Dashboard → Apps &amp; Credentials → betreffende Sandbox-/Live-App → Webhooks → Add Webhook.</p><p class="mb-0">Genau dieses Event auswählen: <code>PAYMENT.CAPTURE.COMPLETED</code>. Nicht „All Events“ verwenden. Anschließend die erzeugte Webhook-ID oben eintragen.</p></div></div>
<?php if ($this->paypalAdmin['store']['writable']) echo $this->form->renderField('paypal_configuration_submitted'); ?>
</div>
<?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php endif; ?>
<?php echo HTMLHelper::_('uitab.endTabSet'); ?>
<?php echo $this->form->renderField('id'); ?><input type="hidden" name="task" value=""><?php echo HTMLHelper::_('form.token'); ?>
</form>
