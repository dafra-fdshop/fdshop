<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$a       = $this->account;
$user    = $a['user'];
$profile = $a['profile'];
$config  = $a['config'];
$section = $this->section;
$base    = 'index.php?option=com_fdshop&view=account';
$money   = static fn($value, $currency = 'EUR') => number_format((float) $value, 2, ',', '.') . ' ' . htmlspecialchars((string) $currency, ENT_QUOTES, 'UTF-8');
$date    = static fn($value) => $value ? HTMLHelper::_('date', $value, 'd.m.Y H:i') : '–';
$status  = static fn($value) => match ((string) $value) {
    'declared' => 'Eingegangen', 'accepted' => 'Akzeptiert', 'rejected' => 'Abgelehnt', default => 'Nicht erklärt',
};
$nav = [
    'overview' => ['icon-home', 'Übersicht'],
    'profile'  => ['icon-user', 'Persönliche Daten'],
    'security' => ['icon-lock', 'Konto & Sicherheit'],
    'orders'   => ['icon-box', 'Bestellungen'],
    'f3'       => ['icon-shield', 'F3-Berechtigung'],
];
?>
<div class="fdshop-account">
    <header class="fdshop-account__hero">
        <div><span class="fdshop-account__eyebrow">FDShop Kundenbereich</span><h1>Mein Konto</h1><p>Willkommen, <?php echo $this->escape((string) ($profile['first_name'] ?: $user->name)); ?>.</p></div>
        <a class="fdshop-account__logout" href="<?php echo Route::_('index.php?option=com_users&task=user.logout&' . Factory::getApplication()->getSession()->getFormToken() . '=1'); ?>">Abmelden</a>
    </header>

    <div class="fdshop-account__layout">
        <nav class="fdshop-account__nav" aria-label="Mein Konto">
            <?php foreach ($nav as $key => [$icon, $label]) : ?>
                <a class="<?php echo $section === $key ? 'is-active' : ''; ?>" href="<?php echo Route::_($base . '&section=' . $key); ?>"<?php echo $section === $key ? ' aria-current="page"' : ''; ?>><span class="<?php echo $icon; ?>" aria-hidden="true"></span><?php echo $label; ?></a>
            <?php endforeach; ?>
        </nav>

        <main class="fdshop-account__main">
        <?php if ($section === 'overview') : ?>
            <section class="fdshop-account__heading"><h2>Alles Wichtige auf einen Blick</h2><p>Verwalten Sie Ihre Daten und behalten Sie Bestellungen und Berechtigungen im Blick.</p></section>
            <div class="fdshop-account__quickgrid">
                <a class="fdshop-account__quick" href="<?php echo Route::_($base . '&section=orders'); ?>"><span class="icon-box" aria-hidden="true"></span><strong>Meine Bestellungen</strong><span><?php echo (int) $a['total']; ?> Bestellung<?php echo (int) $a['total'] === 1 ? '' : 'en'; ?></span></a>
                <a class="fdshop-account__quick" href="<?php echo Route::_($base . '&section=profile'); ?>"><span class="icon-user" aria-hidden="true"></span><strong>Persönliche Daten</strong><span>Liefer- und Kontaktdaten pflegen</span></a>
                <a class="fdshop-account__quick" href="<?php echo Route::_($base . '&section=security'); ?>"><span class="icon-lock" aria-hidden="true"></span><strong>Konto & Sicherheit</strong><span>E-Mail und Zugangsdaten</span></a>
                <a class="fdshop-account__quick" href="<?php echo Route::_($base . '&section=f3'); ?>"><span class="icon-shield" aria-hidden="true"></span><strong>F3-Berechtigung</strong><span><?php echo $a['buyer_status'] === 'permit_holder' ? 'Aktiv' : 'Nicht aktiv'; ?></span></a>
            </div>
            <?php if (!empty($a['orders'])) : $last = $a['orders'][0]; ?>
            <section class="fdshop-account__panel"><div class="fdshop-account__panelhead"><h2>Letzte Bestellung</h2><a href="<?php echo Route::_($base . '&section=orders'); ?>">Alle anzeigen</a></div>
                <div class="fdshop-account__orderline"><div><strong><?php echo $this->escape((string) $last->order_number); ?></strong><span><?php echo $date($last->created); ?></span></div><span class="fdshop-account__badge"><?php echo $this->escape((string) ($last->status_name ?: $last->order_status)); ?></span><strong><?php echo $money($last->grand_total, $last->currency); ?></strong><a class="fdshop-account__button fdshop-account__button--quiet" href="<?php echo Route::_($base . '&section=orders&order_id=' . (int) $last->id); ?>">Details</a></div>
            </section>
            <?php endif; ?>
            <?php if (!empty($a['history'])) : ?><section class="fdshop-account__panel"><h2>Letzte Kontoaktivitäten</h2><ol class="fdshop-account__history"><?php foreach ($a['history'] as $entry) : ?><li><span><?php echo $this->escape((string) $entry->event_title); ?></span><time><?php echo $date($entry->created); ?></time></li><?php endforeach; ?></ol></section><?php endif; ?>

        <?php elseif ($section === 'profile') : ?>
            <section class="fdshop-account__heading"><h2>Persönliche Daten</h2><p>Diese Daten werden bei einer neuen Bestellung in den unveränderlichen Bestellsnapshot übernommen.</p></section>
            <form class="fdshop-account__panel fdshop-account__form" method="post" action="<?php echo Route::_('index.php?option=com_fdshop&task=account.saveProfile'); ?>">
                <div class="fdshop-account__fields">
                <?php foreach (['first_name'=>'Vorname','last_name'=>'Nachname','company'=>'Firma','street'=>'Straße und Hausnummer','postal_code'=>'Postleitzahl','city'=>'Ort','country'=>'Land','phone'=>'Telefon'] as $field=>$label) : if(empty($a['profile_config']['active'][$field]))continue;$required=in_array($field,$a['profile_config']['required'],true); ?>
                    <label class="<?php echo in_array($field, ['street','company'], true) ? 'is-wide' : ''; ?>"><span><?php echo $label; ?><?php echo $required ? ' *' : ' (optional)'; ?></span><input type="text" name="profile[<?php echo $field; ?>]" value="<?php echo $this->escape((string) $profile[$field]); ?>" maxlength="255"<?php echo $required ? ' required' : ''; ?> autocomplete="<?php echo $field === 'postal_code' ? 'postal-code' : ($field === 'phone' ? 'tel' : $field); ?>"></label>
                <?php endforeach; ?>
                </div><button class="fdshop-account__button" type="submit">Änderungen speichern</button><?php echo HTMLHelper::_('form.token'); ?>
            </form>

        <?php elseif ($section === 'security') : ?>
            <section class="fdshop-account__heading"><h2>Konto & Sicherheit</h2><p>Ändern Sie Zugangsdaten bewusst und getrennt von Ihren Bestelldaten.</p></section>
            <form class="fdshop-account__panel fdshop-account__form" method="post" action="<?php echo Route::_('index.php?option=com_fdshop&task=account.saveCredentials'); ?>">
                <h3>Zugangsdaten</h3><label><span>Benutzername</span><input type="text" name="username" value="<?php echo $this->escape((string) $user->username); ?>" autocomplete="username"<?php echo empty($a['username_change_allowed']) ? ' readonly aria-describedby="username-note"' : ''; ?>></label><?php if (empty($a['username_change_allowed'])) : ?><p id="username-note" class="fdshop-account__hint">Der Benutzername kann laut Joomla-Konfiguration nicht geändert werden.</p><?php endif; ?>
                <label><span>Neues Passwort</span><input type="password" name="password" minlength="12" autocomplete="new-password"><small>Leer lassen, wenn das Passwort unverändert bleiben soll. Mindestens 12 Zeichen.</small></label><label><span>Neues Passwort bestätigen</span><input type="password" name="password2" autocomplete="new-password"></label>
                <button class="fdshop-account__button" type="submit">Zugangsdaten speichern</button><?php echo HTMLHelper::_('form.token'); ?>
            </form>
            <form class="fdshop-account__panel fdshop-account__form" method="post" action="<?php echo Route::_('index.php?option=com_fdshop&task=account.requestEmail'); ?>">
                <h3>E-Mail-Adresse</h3><p>Aktuell: <strong><?php echo $this->escape((string) $user->email); ?></strong></p><label><span>Neue E-Mail-Adresse</span><input type="email" name="email" required autocomplete="email"></label><p class="fdshop-account__hint">Die Adresse ändert sich erst, nachdem Sie den Link in der Bestätigungsmail geöffnet haben. Der Link ist zwei Stunden und nur einmal gültig.</p><button class="fdshop-account__button" type="submit">Bestätigung anfordern</button><?php echo HTMLHelper::_('form.token'); ?>
            </form>

        <?php elseif ($section === 'orders') : ?>
            <section class="fdshop-account__heading"><h2>Meine Bestellungen</h2><p>Hier sehen Sie ausschließlich die Bestellungen Ihres angemeldeten Joomla-Kontos.</p></section>
            <?php if ($a['selected']) : $order=$a['selected']; ?>
                <article class="fdshop-account__panel fdshop-account__orderdetail"><div class="fdshop-account__panelhead"><div><a href="<?php echo Route::_($base.'&section=orders'); ?>">← Zur Übersicht</a><h2>Bestellung <?php echo $this->escape((string)$order->order_number); ?></h2></div><span class="fdshop-account__badge"><?php echo $this->escape((string)($order->status_name ?: $order->order_status)); ?></span></div>
                    <dl class="fdshop-account__facts"><div><dt>Bestellt</dt><dd><?php echo $date($order->created); ?></dd></div><div><dt>Zahlungsart</dt><dd><?php echo $this->escape((string)$order->payment_method_name); ?></dd></div><div><dt>Abholung/Versand</dt><dd><?php echo $this->escape((string)$order->shipment_name); ?></dd></div><div><dt>Gesamtbetrag</dt><dd><?php echo $money($order->grand_total,$order->currency); ?></dd></div></dl>
                    <h3>Positionen</h3><div class="fdshop-account__items"><?php foreach ($a['items'] as $item) : ?><div><span><strong><?php echo $this->escape((string)$item->product_name); ?></strong><small><?php echo $this->escape((string)$item->sku); ?> · Menge <?php echo $this->escape((string)$item->quantity); ?></small></span><strong><?php echo $money($item->line_total_gross,$item->currency); ?></strong></div><?php endforeach; ?><?php foreach ($a['bundles'] as $bundle) : ?><div><span><strong><?php echo $this->escape((string)$bundle->bundle_name); ?></strong><small>Bundle · Menge <?php echo $this->escape((string)$bundle->quantity); ?></small></span><strong><?php echo $money($bundle->line_total_gross,$bundle->currency); ?></strong></div><?php endforeach; ?></div>
                    <dl class="fdshop-account__totals"><div><dt>Produkt-/Bundlesumme</dt><dd><?php echo $money($order->subtotal,$order->currency); ?></dd></div><?php if((float)$order->coupon_discount>0):?><div><dt>Gutschein<?php echo $order->coupon_code?' '.$this->escape((string)$order->coupon_code):''; ?></dt><dd>− <?php echo $money($order->coupon_discount,$order->currency); ?></dd></div><?php endif; ?><div><dt>Versand-/Abholgebühr</dt><dd><?php echo $money($order->shipment_fee,$order->currency); ?></dd></div><div><dt>Zahlungsgebühr</dt><dd><?php echo $money($order->payment_fee,$order->currency); ?></dd></div><div class="is-total"><dt>Gesamtbetrag</dt><dd><?php echo $money($order->grand_total,$order->currency); ?></dd></div></dl><?php if(trim((string)$order->order_note)!==''):?><div class="fdshop-account__notice"><strong>Bemerkung</strong><br><?php echo nl2br($this->escape((string)$order->order_note)); ?></div><?php endif; ?>
                    <div class="fdshop-account__actions">
                        <?php if (!empty($order->open_shipment_request_id)) : ?><span class="fdshop-account__notice">Änderungsanfrage zur Abholstation ist offen.</span><?php else : ?><button class="fdshop-account__button fdshop-account__button--quiet" type="button" data-dialog="shipment-dialog">Abholstation ändern</button><?php endif; ?>
                        <?php if ((string)$order->withdrawal_status === 'none') : ?><button class="fdshop-account__button fdshop-account__button--danger" type="button" data-dialog="withdrawal-dialog">Widerruf erklären</button><?php else : ?><span class="fdshop-account__notice">Widerruf: <?php echo $status($order->withdrawal_status); ?><?php echo $order->withdrawal_decision_text ? ' – '.$this->escape((string)$order->withdrawal_decision_text) : ''; ?></span><?php endif; ?>
                    </div>
                </article>
                <dialog id="shipment-dialog" class="fdshop-account__dialog"><form method="post" action="<?php echo Route::_('index.php?option=com_fdshop&task=account.requestShipment'); ?>"><button class="fdshop-account__dialogclose" type="button" data-close aria-label="Schließen">×</button><h2>Abholstation anfragen</h2><p><?php echo nl2br($this->escape((string)($config->account_shipment_request_text ?? 'Die Bestellung wird nicht automatisch geändert. Wir prüfen Ihre Anfrage und melden uns.'))); ?></p><label><span>Gewünschte Station</span><select name="shipment_id" required><option value="">Bitte wählen</option><?php foreach ($a['shipments'] as $shipment) : if((int)$shipment->id===(int)$order->shipment_id)continue; ?><option value="<?php echo (int)$shipment->id; ?>"><?php echo $this->escape((string)$shipment->shipment_name); ?></option><?php endforeach; ?></select></label><input type="hidden" name="order_id" value="<?php echo (int)$order->id; ?>"><button class="fdshop-account__button" type="submit">Anfrage verbindlich senden</button><?php echo HTMLHelper::_('form.token'); ?></form></dialog>
                <dialog id="withdrawal-dialog" class="fdshop-account__dialog"><form method="post" action="<?php echo Route::_('index.php?option=com_fdshop&task=account.withdraw'); ?>"><button class="fdshop-account__dialogclose" type="button" data-close aria-label="Schließen">×</button><h2>Widerruf erklären</h2><p><strong><?php echo $this->escape(trim((string)$order->customer_first_name.' '.(string)$order->customer_last_name)); ?></strong><br><?php echo $this->escape((string)$order->customer_email); ?><br>Bestellung <?php echo $this->escape((string)$order->order_number); ?></p><?php $deadline=(new DateTimeImmutable((string)$order->created))->modify('+'.max(1,(int)($config->account_withdrawal_days??14)).' days');if(new DateTimeImmutable()>$deadline):?><div class="fdshop-account__notice"><?php echo nl2br($this->escape((string)($config->account_withdrawal_expired_text??''))); ?></div><?php endif; ?><p>Mit dem Absenden erklären Sie den Widerruf. Wir bestätigen den Eingang per E-Mail und prüfen den Vorgang.</p><input type="hidden" name="order_id" value="<?php echo (int)$order->id; ?>"><div class="fdshop-account__actions"><button class="fdshop-account__button fdshop-account__button--quiet" type="button" data-close>Widerruf abbrechen</button><button class="fdshop-account__button fdshop-account__button--danger" type="submit">Widerruf bestätigen</button></div><?php echo HTMLHelper::_('form.token'); ?></form></dialog>
            <?php elseif (empty($a['orders'])) : ?><div class="fdshop-account__empty"><span class="icon-box" aria-hidden="true"></span><h3>Noch keine Bestellungen</h3><p>Sobald Sie bestellt haben, finden Sie hier den aktuellen Stand.</p></div>
            <?php else : ?><div class="fdshop-account__orders"><?php foreach ($a['orders'] as $order) : ?><article><div><strong><?php echo $this->escape((string)$order->order_number); ?></strong><span><?php echo $date($order->created); ?></span><span><?php echo $this->escape((string)$order->shipment_name); ?> · <?php echo $this->escape((string)$order->payment_method_name); ?></span><?php if(!empty($order->open_shipment_request_id)):?><span>Änderungsanfrage zur Abholstation offen</span><?php endif; ?><?php if((string)$order->withdrawal_status!=='none'):?><span>Widerruf: <?php echo $status($order->withdrawal_status); ?></span><?php endif; ?></div><span class="fdshop-account__badge"><?php echo $this->escape((string)($order->status_name ?: $order->order_status)); ?></span><strong><?php echo $money($order->grand_total,$order->currency); ?></strong><a class="fdshop-account__button fdshop-account__button--quiet" href="<?php echo Route::_($base.'&section=orders&order_id='.(int)$order->id); ?>">Details</a></article><?php endforeach; ?></div>
                <?php $pages=(int)ceil($a['total']/$a['limit']);if($pages>1):?><nav class="fdshop-account__pagination" aria-label="Bestellseiten"><?php for($i=1;$i<=$pages;$i++):?><a class="<?php echo $i===$a['page']?'is-active':''; ?>" href="<?php echo Route::_($base.'&section=orders&page='.$i); ?>"><?php echo $i; ?></a><?php endfor;?></nav><?php endif;?>
            <?php endif; ?>

        <?php elseif ($section === 'f3') : ?>
            <section class="fdshop-account__heading"><h2>F3-Berechtigung</h2><p>Die zentrale FDShop-Käuferberechtigung bleibt die einzige fachliche Wahrheit.</p></section>
            <div class="fdshop-account__panel"><div class="fdshop-account__eligibility"><span class="icon-shield" aria-hidden="true"></span><div><strong>Aktueller Status</strong><p><?php echo $a['buyer_status'] === 'permit_holder' ? 'F3-Berechtigung aktiv' : 'Keine aktive F3-Berechtigung'; ?></p><?php if($a['lastF3Submission']):?><small>Unterlagen am <?php echo $date($a['lastF3Submission']); ?> zur Prüfung übermittelt</small><?php endif; ?></div></div><?php echo nl2br($this->escape((string)($config->account_f3_text ?? 'Laden Sie Ihre Nachweise zur manuellen Prüfung hoch. Die Übermittlung schaltet keine Berechtigung automatisch frei.'))); ?></div>
            <?php if($a['buyer_status'] !== 'permit_holder'): ?><form class="fdshop-account__panel fdshop-account__form" method="post" enctype="multipart/form-data" action="<?php echo Route::_('index.php?option=com_fdshop&task=account.uploadF3'); ?>"><h3>Unterlagen sicher übermitteln</h3><p class="fdshop-account__hint">Für die Prüfung werden F3-Schein und Ausweis benötigt; Sie können fehlende Unterlagen bei Bedarf nachreichen. PDF, JPG oder PNG; maximal <?php echo max(1,(int)($config->account_f3_max_mb??8)); ?> MB je Datei.</p><p class="fdshop-account__notice">Die hochgeladenen Unterlagen werden von FDShop ausschließlich zur Übermittlung verwendet und nicht dauerhaft auf dem Webserver gespeichert.</p><label><span>F3-Schein</span><input type="file" name="permit" accept="application/pdf,image/jpeg,image/png"></label><label><span>Identitätsnachweis</span><input type="file" name="identity" accept="application/pdf,image/jpeg,image/png"></label><button class="fdshop-account__button" type="submit">Unterlagen zur Prüfung senden</button><?php echo HTMLHelper::_('form.token'); ?></form><?php endif; ?>
        <?php endif; ?>
        </main>
    </div>
</div>
<script>
document.addEventListener('click',function(e){var open=e.target.closest('[data-dialog]');if(open){var d=document.getElementById(open.dataset.dialog);if(d&&d.showModal)d.showModal();}if(e.target.closest('[data-close]'))e.target.closest('dialog').close();});
</script>
