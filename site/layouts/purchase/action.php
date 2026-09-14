<?php

defined('_JEXEC') or die;

$item = $displayData['item'];
$minimum = $displayData['minimum'];
$step = $displayData['step'];
$maximum = $displayData['maximum'];
$unitVariant = $displayData['unitVariant'] ?? 'piece';
$format = static fn (float $value): string => rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');

if ((string) $item->in_stock === 'Ausverkauft') {
    return;
}
?>
<div class="fdshop-purchase" data-fdshop-purchase data-purchase-product-id="<?php echo (int) $item->id; ?>" data-unit-variant="<?php echo htmlspecialchars($unitVariant, ENT_QUOTES, 'UTF-8'); ?>" data-product-name="<?php echo htmlspecialchars((string) $item->product_name, ENT_QUOTES, 'UTF-8'); ?>">
    <button type="button" class="btn btn-primary fdshop-purchase__button" data-purchase-submit aria-label="<?php echo htmlspecialchars((string) $item->product_name, ENT_QUOTES, 'UTF-8'); ?> in den Warenkorb legen" title="In den Warenkorb"><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i></button>
    <label class="visually-hidden" for="fdshop-purchase-quantity-<?php echo (int) $item->id; ?>">Menge für <?php echo htmlspecialchars((string) $item->product_name, ENT_QUOTES, 'UTF-8'); ?></label>
    <input id="fdshop-purchase-quantity-<?php echo (int) $item->id; ?>" class="fdshop-purchase__quantity" type="number" value="<?php echo $format($minimum); ?>" min="<?php echo $format($minimum); ?>"<?php echo $maximum > 0 ? ' max="' . $format($maximum) . '"' : ''; ?> step="<?php echo $format($step); ?>" inputmode="decimal" data-purchase-quantity>
    <p class="fdshop-purchase__error" data-purchase-error role="alert" hidden></p>
</div>
