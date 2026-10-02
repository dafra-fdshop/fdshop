<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

$cart = $this->cart;
$currency = (string) ($cart['currency'] ?? 'EUR');
?>
<main class="fdshop-cart" data-fdshop-cart data-paypal-enabled="<?php echo (int) ($cart['payment']->paypal_enabled ?? 0); ?>">
    <h1>Warenkorb</h1>
        <?php if ($this->cartConflict) : ?>
        <div class="fdshop-cart-conflict-pending alert alert-warning" role="status"><span>Vor dem Fortsetzen ist eine Warenkorbauswahl erforderlich.</span> <button type="button" class="btn btn-sm btn-outline-dark" data-cart-conflict-open>Warenkörbe vergleichen</button></div>
        <?php endif; ?>
        <div class="fdshop-cart__message" data-fdshop-cart-message role="status" aria-live="polite" hidden></div>
        <div class="fdshop-cart__grid">
            <section class="fdshop-cart__products" aria-labelledby="fdshop-cart-products-heading">
                <h2 id="fdshop-cart-products-heading">Produkte</h2>
                <div class="fdshop-cart__empty" data-fdshop-cart-empty<?php echo $cart['items'] === [] && $cart['bundles'] === [] ? '' : ' hidden'; ?>>Aktuell sind noch keine Produkte im Warenkorb.</div>
                <div class="fdshop-cart__table" data-fdshop-cart-items<?php echo $cart['items'] === [] ? ' hidden' : ''; ?>>
                    <div class="fdshop-cart__table-head" aria-hidden="true"><span>Artikel-Nr.</span><span>Produkt</span><span>Einzelpreis</span><span>Menge</span><span>Betrag</span></div>
                    <?php foreach ($cart['items'] as $item) : ?>
                        <article class="fdshop-cart__item" data-cart-item="<?php echo (int) $item->id; ?>" data-confirmed-quantity="<?php echo $this->escape($item->quantity_formatted); ?>">
                            <div class="fdshop-cart__sku"><span class="fdshop-cart__mobile-label">Artikel-Nr.</span><?php echo $this->escape((string) $item->sales_sku); ?></div>
                            <div class="fdshop-cart__product"><a href="<?php echo $this->escape($item->product_url); ?>"><img src="<?php echo $this->escape($item->image_url); ?>" alt="" width="88" height="88"><span><?php echo $this->escape((string) $item->sales_name); ?><?php if ((string) $item->unit_variant === 'package') : ?><small><?php echo (int) $item->unit_quantity_snapshot; ?> Stück je <?php echo $this->escape((string) $item->unit_type_snapshot); ?></small><?php endif; ?></span></a></div>
                            <div class="fdshop-cart__unit-price"><span class="fdshop-cart__mobile-label">Einzelpreis</span><?php if ($item->has_discount) : ?><span class="fdshop-cart__regular-price"><?php echo $this->escape($item->regular_price_formatted); ?></span><?php endif; ?><strong data-cart-unit-price><?php echo $this->escape($item->unit_price_formatted); ?></strong></div>
                            <div class="fdshop-cart__quantity">
                                <label class="visually-hidden" for="fdshop-cart-quantity-<?php echo (int) $item->id; ?>">Menge für <?php echo $this->escape((string) $item->product_name); ?></label>
                                <div class="fdshop-cart__quantity-controls">
                                    <button type="button" class="btn btn-outline-secondary" data-cart-decrease aria-label="Menge reduzieren">−</button>
                                    <input id="fdshop-cart-quantity-<?php echo (int) $item->id; ?>" class="form-control" type="number" value="<?php echo $this->escape($item->quantity_formatted); ?>" min="<?php echo $this->escape((string) $item->min_order_qty); ?>"<?php echo $item->max_order_qty > 0 ? ' max="' . $this->escape((string) $item->max_order_qty) . '"' : ''; ?> step="<?php echo $this->escape((string) $item->step_order_qty); ?>" inputmode="decimal" data-cart-quantity>
                                    <button type="button" class="btn btn-outline-secondary" data-cart-increase aria-label="Menge erhöhen">+</button>
                                    <button type="button" class="btn btn-primary" data-cart-update aria-label="Menge aktualisieren" title="Menge aktualisieren"><span class="fa-solid fa-rotate" aria-hidden="true"></span></button>
                                    <button type="button" class="btn btn-outline-danger" data-cart-remove aria-label="<?php echo $this->escape((string) $item->product_name); ?> entfernen" title="Produkt entfernen"><span class="fa-solid fa-trash" aria-hidden="true"></span></button>
                                </div>
                            </div>
                            <strong class="fdshop-cart__line-total" data-cart-line-total><span class="fdshop-cart__mobile-label">Betrag</span><span data-cart-line-value><?php echo $this->escape($item->line_total_formatted); ?></span></strong>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php foreach ($cart['bundles'] as $bundle) : ?>
                    <article class="fdshop-cart__bundle" data-cart-bundle="<?php echo (int) $bundle->id; ?>">
                        <header><div><strong><?php echo $this->escape((string) $bundle->bundle_name); ?></strong><small> <?php echo $this->escape((string) $bundle->bundle_number); ?></small></div><strong><?php echo $this->escape($this->formatPrice((float) $bundle->total_gross, (string) $bundle->currency)); ?></strong></header>
                        <ul><?php foreach ($bundle->items as $bundleItem) : ?><li><?php echo (int) $bundleItem->quantity; ?> × <?php echo $this->escape((string) $bundleItem->product_name); ?> <small>(<?php echo $this->escape((string) $bundleItem->sku); ?>)</small></li><?php endforeach; ?></ul>
                        <p><?php echo (int) $bundle->total_quantity; ?> Stück · Bundle-Rabatt <?php echo $this->escape(number_format((float) $bundle->discount_percent, 2, ',', '.')); ?> %</p>
                        <div><a class="btn btn-outline-primary btn-sm" href="index.php?option=com_fdshop&amp;view=product&amp;id=<?php echo (int) ($bundle->items[0]->product_id ?? 0); ?>&amp;bundle_id=<?php echo (int) $bundle->bundle_id; ?>&amp;cart_bundle_id=<?php echo (int) $bundle->id; ?>">Bearbeiten</a> <button type="button" class="btn btn-outline-danger btn-sm" data-cart-bundle-remove>Bundle entfernen</button></div>
                    </article>
                <?php endforeach; ?>
            </section>
            <aside class="fdshop-cart__checkout" aria-labelledby="fdshop-cart-summary-heading">
                <h2 id="fdshop-cart-summary-heading">Bestellübersicht</h2>
                <div class="fdshop-cart__subtotal"><span>Summe der Produktpreise</span><strong data-cart-subtotal><?php echo $this->escape($this->formatPrice((float) $cart['subtotal'], $currency)); ?></strong></div>
                <section class="fdshop-cart__coupon"><h3>Gutschein</h3><div class="input-group"><input class="form-control" type="text" aria-label="Gutscheincode" placeholder="Gutscheincode" value="<?php echo $this->escape((string) ($cart['coupon_code'] ?? '')); ?>" data-cart-coupon-code><button class="btn btn-outline-secondary" type="button" data-cart-apply-coupon>Übernehmen</button></div></section>
                <section class="fdshop-cart__choice"><div><h3>Abholstation</h3><strong data-cart-shipment-name><?php echo $this->escape((string) ($cart['shipment']->name ?? 'Keine aktive Abholstation')); ?></strong></div><strong data-cart-shipment-selection-fee><?php echo $this->escape($this->formatPrice((float) $cart['shipment_fee'], $currency)); ?></strong><button type="button" class="btn btn-link" data-cart-open="shipment">ändern</button></section>
                <section class="fdshop-cart__choice"><div><h3>Zahlungsart</h3><strong data-cart-payment-name><?php echo $this->escape((string) ($cart['payment']->name ?? 'Keine aktive Zahlungsart')); ?></strong></div><strong data-cart-payment-selection-fee><?php echo $this->escape($this->formatPrice((float) $cart['payment_fee'], $currency)); ?></strong><button type="button" class="btn btn-link" data-cart-open="payment">ändern</button></section>
                <dl class="fdshop-cart__totals">
                    <div><dt>Produktsumme</dt><dd data-cart-summary-subtotal><?php echo $this->escape($this->formatPrice((float) $cart['subtotal'], $currency)); ?></dd></div>
                    <div><dt>Gutscheinabzug</dt><dd data-cart-coupon-discount><?php echo $this->escape($this->formatPrice((float) ($cart['coupon_discount'] ?? 0), $currency)); ?></dd></div>
                    <div><dt>Versand-/Abholgebühr</dt><dd data-cart-shipment-fee><?php echo $this->escape($this->formatPrice((float) $cart['shipment_fee'], $currency)); ?></dd></div>
                    <div><dt>Zahlungsgebühr</dt><dd data-cart-payment-fee><?php echo $this->escape($this->formatPrice((float) $cart['payment_fee'], $currency)); ?></dd></div>
                    <div class="fdshop-cart__grand-total"><dt>Gesamtbetrag</dt><dd data-cart-total><?php echo $this->escape($this->formatPrice((float) $cart['total'], $currency)); ?></dd></div>
                </dl>
                <div class="fdshop-cart__remark"><label for="fdshop-cart-remark">Bemerkung zur Bestellung</label><textarea id="fdshop-cart-remark" class="form-control" rows="3" data-cart-remark></textarea></div>
                <?php if ((int) $this->config->show_terms_checkbox === 1) : ?><div class="form-check fdshop-cart__terms"><input id="fdshop-cart-terms" class="form-check-input" type="checkbox" data-cart-terms data-required="<?php echo (int) $this->config->require_terms_checkbox; ?>"><label class="form-check-label" for="fdshop-cart-terms">Ich bestätige die AGB und die Widerrufsbelehrung.</label></div><?php endif; ?>
                <button type="button" class="btn btn-primary btn-lg fdshop-cart__order" data-cart-order data-cart-guest="<?php echo $this->guest ? '1' : '0'; ?>"><?php echo $this->guest ? 'Weiter zum Bestellen' : 'Zahlungspflichtig bestellen'; ?></button>
                <div class="alert alert-info mt-3" data-paypal-progress hidden><strong>Zeit für den Abschluss Ihrer Zahlung: <span data-paypal-countdown>10:00</span></strong><div>Bitte schließen Sie den Bezahlvorgang innerhalb der verfügbaren Zeit ab.</div></div>
            </aside>
        </div>
        <dialog class="fdshop-cart__dialog" data-cart-dialog="shipment"><form method="dialog"><header><h2>Abholstation wählen</h2><button value="cancel" aria-label="Auswahl schließen">×</button></header><?php foreach ($cart['shipments'] as $shipment) : ?><button type="button" class="fdshop-cart__option" data-cart-select-shipment="<?php echo (int) $shipment->id; ?>"><strong><?php echo $this->escape((string) $shipment->name); ?></strong><span><?php echo $this->escape($this->formatPrice((float) $shipment->fee, $currency)); ?></span></button><?php endforeach; ?></form></dialog>
        <dialog class="fdshop-cart__dialog" data-cart-dialog="payment"><form method="dialog"><header><h2>Zahlungsart wählen</h2><button value="cancel" aria-label="Auswahl schließen">×</button></header><?php foreach ($cart['payments'] as $payment) : ?><button type="button" class="fdshop-cart__option" data-cart-select-payment="<?php echo (int) $payment->id; ?>" data-paypal-enabled="<?php echo (int) ($payment->paypal_enabled ?? 0); ?>"><strong><?php echo $this->escape((string) $payment->name); ?></strong><span><?php echo $this->escape($this->formatPrice((float) $payment->fee, $currency)); ?></span></button><?php endforeach; ?></form></dialog>
        <?php if ($this->cartConflict) : ?>
        <dialog class="fdshop-cart-conflict" data-cart-conflict aria-labelledby="fdshop-cart-conflict-title" aria-describedby="fdshop-cart-conflict-description">
            <div class="fdshop-cart-conflict__content">
                <button type="button" class="fdshop-cart-conflict__close" data-cart-conflict-close aria-label="Warenkorbauswahl schließen">×</button>
                <h2 id="fdshop-cart-conflict-title">Welchen Warenkorb möchtest du verwenden?</h2>
                <p id="fdshop-cart-conflict-description">Du hast vor deiner Anmeldung einen neuen Warenkorb zusammengestellt. In deinem Kundenkonto ist bereits ein gespeicherter Warenkorb vorhanden. Bitte wähle aus, mit welchem du fortfahren möchtest.</p>
                <div class="fdshop-cart-conflict__choices">
                <?php foreach (['guest' => ['AKTUELLER WARENKORB', 'Gerade zusammengestellt'], 'user' => ['GESPEICHERTER WARENKORB', 'Aus deinem Kundenkonto']] as $key => [$title, $origin]) : $summary = $this->cartConflict[$key]; ?>
                    <section class="fdshop-cart-conflict__choice" aria-labelledby="fdshop-cart-conflict-<?php echo $key; ?>">
                        <h3 id="fdshop-cart-conflict-<?php echo $key; ?>"><?php echo $title; ?></h3>
                        <strong><?php echo (int) $summary['count']; ?> Position<?php echo (int) $summary['count'] === 1 ? '' : 'en'; ?> · <?php echo $this->escape((string) $summary['total']); ?></strong>
                        <span><?php echo $origin; ?></span>
                        <?php if ($summary['names']) : ?><ul><?php foreach ($summary['names'] as $name) : ?><li><?php echo $this->escape((string) $name); ?></li><?php endforeach; ?><?php if ($summary['remaining'] > 0) : ?><li>+<?php echo (int) $summary['remaining']; ?> weitere</li><?php endif; ?></ul><?php endif; ?>
                        <button type="button" class="btn btn-primary" data-cart-conflict-choice="<?php echo $key; ?>">Diesen verwenden</button>
                    </section>
                <?php endforeach; ?>
                </div>
                <p class="fdshop-cart-conflict__notice">Der nicht ausgewählte Warenkorb wird verworfen. Die beiden Warenkörbe werden nicht zusammengeführt.</p>
                <div class="alert alert-danger" data-cart-conflict-error role="alert" hidden></div>
            </div>
        </dialog>
        <?php endif; ?>
        <form hidden data-cart-token><?php echo HTMLHelper::_('form.token'); ?></form>
        <input type="hidden" value="<?php echo $this->escape($this->submissionId); ?>" data-cart-submission>
</main>
