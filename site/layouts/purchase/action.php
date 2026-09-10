<?php

defined('_JEXEC') or die;

$item = $displayData['item'];
$variant = $displayData['variant'];
$minimum = $displayData['minimum'];
$step = $displayData['step'];
$maximum = $displayData['maximum'];
$available = $displayData['available'];
$format = static fn (float $value): string => rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');

if ($available + 0.0001 < $minimum) {
    return;
}
?>
<div class="fdshop-purchase fdshop-purchase--<?php echo htmlspecialchars($variant, ENT_QUOTES, 'UTF-8'); ?>" data-fdshop-purchase data-purchase-product-id="<?php echo (int) $item->id; ?>" data-product-name="<?php echo htmlspecialchars((string) $item->product_name, ENT_QUOTES, 'UTF-8'); ?>">
    <button type="button" class="btn btn-primary fdshop-purchase__button" data-purchase-submit aria-label="<?php echo htmlspecialchars((string) $item->product_name, ENT_QUOTES, 'UTF-8'); ?> in den Warenkorb legen" title="In den Warenkorb"><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i></button>
    <label class="visually-hidden" for="fdshop-purchase-quantity-<?php echo $variant . '-' . (int) $item->id; ?>">Menge für <?php echo htmlspecialchars((string) $item->product_name, ENT_QUOTES, 'UTF-8'); ?></label>
    <input id="fdshop-purchase-quantity-<?php echo $variant . '-' . (int) $item->id; ?>" class="form-control fdshop-purchase__quantity" type="number" value="<?php echo $format($minimum); ?>" min="<?php echo $format($minimum); ?>"<?php echo $maximum > 0 ? ' max="' . $format($maximum) . '"' : ''; ?> step="<?php echo $format($step); ?>" inputmode="decimal" data-purchase-quantity>
    <p class="fdshop-purchase__error" data-purchase-error role="alert" hidden></p>
</div>
