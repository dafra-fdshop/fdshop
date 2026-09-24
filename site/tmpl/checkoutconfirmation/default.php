<?php defined('_JEXEC') or die; $order=$this->confirmation['order']; ?>
<main class="fdshop-checkout-confirmation">
 <h1>Vielen Dank für Ihre Bestellung</h1>
 <p>Ihre Bestellnummer: <strong><?php echo $this->escape((string)$order->order_number); ?></strong></p>
 <p>Status: <?php echo $this->escape((string)$order->order_status); ?></p>
 <h2>Bestellpositionen</h2><ul><?php foreach($this->confirmation['items'] as $item): ?><li><?php echo $this->escape((string)$item->quantity); ?> × <?php echo $this->escape((string)$item->product_name); ?> – <?php echo number_format((float)$item->line_total_gross,2,',','.'); ?> <?php echo $this->escape((string)$item->currency); ?></li><?php endforeach; ?></ul>
 <dl><div><dt>Produktsumme</dt><dd><?php echo number_format((float)$order->subtotal,2,',','.'); ?> <?php echo $this->escape((string)$order->currency); ?></dd></div><div><dt>Gutschein</dt><dd>-<?php echo number_format((float)$order->coupon_discount,2,',','.'); ?></dd></div><div><dt>Abholung/Versand</dt><dd><?php echo $this->escape((string)$order->shipment_name); ?> (<?php echo number_format((float)$order->shipment_fee,2,',','.'); ?>)</dd></div><div><dt>Zahlungsart</dt><dd><?php echo $this->escape((string)$order->payment_method_name); ?> (<?php echo number_format((float)$order->payment_fee,2,',','.'); ?>)</dd></div><div><dt>Gesamtbetrag</dt><dd><strong><?php echo number_format((float)$order->grand_total,2,',','.'); ?> <?php echo $this->escape((string)$order->currency); ?></strong></dd></div></dl>
 <?php if((string)$order->order_note!==''):?><h2>Bemerkung</h2><p><?php echo nl2br($this->escape((string)$order->order_note)); ?></p><?php endif; ?>
</main>
