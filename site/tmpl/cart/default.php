<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$cart = $this->cart;
$currency = (string) ($cart['currency'] ?? 'EUR');
?>
<main class="fdshop-cart" data-fdshop-cart>
    <h1>Warenkorb</h1>
    <?php if ($cart === null) : ?>
        <div class="alert alert-info" role="status">Bitte melden Sie sich an, um den Warenkorb zu verwenden und später eine Bestellung abzuschließen.</div>
    <?php else : ?>
        <div class="fdshop-cart__message" data-fdshop-cart-message role="status" aria-live="polite" hidden></div>
        <div class="fdshop-cart__grid">
            <section class="fdshop-cart__products" aria-labelledby="fdshop-cart-products-heading">
                <h2 id="fdshop-cart-products-heading">Produkte</h2>
                <div class="fdshop-cart__empty" data-fdshop-cart-empty<?php echo $cart['items'] === [] ? '' : ' hidden'; ?>>Aktuell sind noch keine Produkte im Warenkorb.</div>
                <div class="fdshop-cart__table" data-fdshop-cart-items<?php echo $cart['items'] === [] ? ' hidden' : ''; ?>>
                    <div class="fdshop-cart__table-head" aria-hidden="true"><span>Artikel-Nr.</span><span>Produkt</span><span>Einzelpreis</span><span>Menge</span><span>Betrag</span></div>
                    <?php foreach ($cart['items'] as $item) : ?>
                        <article class="fdshop-cart__item" data-cart-item="<?php echo (int) $item->id; ?>" data-confirmed-quantity="<?php echo $this->escape($item->quantity_formatted); ?>">
                            <div class="fdshop-cart__sku"><span class="fdshop-cart__mobile-label">Artikel-Nr.</span><?php echo $this->escape((string) $item->sku); ?></div>
                            <div class="fdshop-cart__product"><a href="<?php echo $this->escape($item->product_url); ?>"><img src="<?php echo $this->escape($item->image_url); ?>" alt="" width="88" height="88"><span><?php echo $this->escape((string) $item->product_name); ?></span></a></div>
                            <div class="fdshop-cart__unit-price"><span class="fdshop-cart__mobile-label">Einzelpreis</span><?php if ($item->has_discount) : ?><span class="fdshop-cart__regular-price"><?php echo $this->escape($item->regular_price_formatted); ?></span><?php endif; ?><strong data-cart-unit-price><?php echo $this->escape($item->unit_price_formatted); ?></strong></div>
                            <div class="fdshop-cart__quantity">
                                <label class="visually-hidden" for="fdshop-cart-quantity-<?php echo (int) $item->id; ?>">Menge für <?php echo $this->escape((string) $item->product_name); ?></label>
                                <div class="fdshop-cart__quantity-controls">
                                    <button type="button" class="btn btn-outline-secondary" data-cart-decrease aria-label="Menge reduzieren">−</button>
                                    <input id="fdshop-cart-quantity-<?php echo (int) $item->id; ?>" class="form-control" type="number" value="<?php echo $this->escape($item->quantity_formatted); ?>" min="<?php echo $this->escape((string) $item->min_order_qty); ?>" max="<?php echo $this->escape((string) $item->max_order_qty); ?>" step="<?php echo $this->escape((string) $item->step_order_qty); ?>" inputmode="decimal" data-cart-quantity>
                                    <button type="button" class="btn btn-outline-secondary" data-cart-increase aria-label="Menge erhöhen">+</button>
                                    <button type="button" class="btn btn-primary" data-cart-update>Aktualisieren</button>
                                    <button type="button" class="btn btn-outline-danger" data-cart-remove aria-label="<?php echo $this->escape((string) $item->product_name); ?> entfernen" title="Produkt entfernen"><span aria-hidden="true">×</span></button>
                                </div>
                            </div>
                            <strong class="fdshop-cart__line-total" data-cart-line-total><span class="fdshop-cart__mobile-label">Betrag</span><span data-cart-line-value><?php echo $this->escape($item->line_total_formatted); ?></span></strong>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
            <aside class="fdshop-cart__checkout" aria-labelledby="fdshop-cart-summary-heading">
                <h2 id="fdshop-cart-summary-heading">Bestellübersicht</h2>
                <dl class="fdshop-cart__totals">
                    <div><dt>Summe der Produktpreise</dt><dd data-cart-subtotal><?php echo $this->escape($this->formatPrice((float) $cart['subtotal'], $currency)); ?></dd></div>
                    <div><dt>Versand-/Abholgebühr</dt><dd data-cart-shipment-fee><?php echo $this->escape($this->formatPrice((float) $cart['shipment_fee'], $currency)); ?></dd></div>
                    <div><dt>Zahlungsgebühr</dt><dd data-cart-payment-fee><?php echo $this->escape($this->formatPrice((float) $cart['payment_fee'], $currency)); ?></dd></div>
                    <div class="fdshop-cart__grand-total"><dt>Gesamtbetrag</dt><dd data-cart-total><?php echo $this->escape($this->formatPrice((float) $cart['total'], $currency)); ?></dd></div>
                </dl>
                <section class="fdshop-cart__coupon"><h3>Gutschein</h3><div class="input-group"><input class="form-control" type="text" aria-label="Gutscheincode" placeholder="Gutscheincode" disabled><button class="btn btn-outline-secondary" type="button" disabled>Übernehmen</button></div><small>Die Gutscheineinlösung wird in einem folgenden Paket aktiviert.</small></section>
                <section class="fdshop-cart__choice"><div><h3>Abholstation</h3><strong data-cart-shipment-name><?php echo $this->escape((string) ($cart['shipment']->name ?? 'Keine aktive Abholstation')); ?></strong></div><button type="button" class="btn btn-link" data-cart-open="shipment">ändern</button></section>
                <section class="fdshop-cart__choice"><div><h3>Zahlungsart</h3><strong data-cart-payment-name><?php echo $this->escape((string) ($cart['payment']->name ?? 'Keine aktive Zahlungsart')); ?></strong></div><button type="button" class="btn btn-link" data-cart-open="payment">ändern</button></section>
                <section class="fdshop-cart__remark"><label for="fdshop-cart-remark">Bemerkung</label><textarea id="fdshop-cart-remark" class="form-control" rows="4" maxlength="2000" data-cart-remark><?php echo $this->escape($this->remark); ?></textarea><button type="button" class="btn btn-outline-primary" data-cart-save-remark>Bemerkung speichern</button></section>
                <div class="form-check fdshop-cart__terms"><input id="fdshop-cart-terms" class="form-check-input" type="checkbox" data-cart-terms data-required="<?php echo (int) $this->config->require_terms_checkbox; ?>"><label class="form-check-label" for="fdshop-cart-terms">Ich bestätige die AGB und die Widerrufsbelehrung.</label></div>
                <button type="button" class="btn btn-primary btn-lg fdshop-cart__order" data-cart-order>Zahlungspflichtig bestellen</button>
                <small>Die Bestellfunktion ist in Warenkorb V1 noch nicht aktiv.</small>
            </aside>
        </div>
        <dialog class="fdshop-cart__dialog" data-cart-dialog="shipment"><form method="dialog"><header><h2>Abholstation wählen</h2><button value="cancel" aria-label="Auswahl schließen">×</button></header><?php foreach ($cart['shipments'] as $shipment) : ?><button type="button" class="fdshop-cart__option" data-cart-select-shipment="<?php echo (int) $shipment->id; ?>"><strong><?php echo $this->escape((string) $shipment->name); ?></strong><span><?php echo $this->escape($this->formatPrice((float) $shipment->fee, $currency)); ?></span></button><?php endforeach; ?></form></dialog>
        <dialog class="fdshop-cart__dialog" data-cart-dialog="payment"><form method="dialog"><header><h2>Zahlungsart wählen</h2><button value="cancel" aria-label="Auswahl schließen">×</button></header><?php foreach ($cart['payments'] as $payment) : ?><button type="button" class="fdshop-cart__option" data-cart-select-payment="<?php echo (int) $payment->id; ?>"><strong><?php echo $this->escape((string) $payment->name); ?></strong><span><?php echo $this->escape($this->formatPrice((float) $payment->fee, $currency)); ?></span></button><?php endforeach; ?></form></dialog>
        <form hidden data-cart-token><?php echo HTMLHelper::_('form.token'); ?></form>
    <?php endif; ?>
</main>
