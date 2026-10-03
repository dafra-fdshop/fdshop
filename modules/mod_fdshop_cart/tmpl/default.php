<?php

defined('_JEXEC') or die;

$id = 'fdshop-cart-module-' . (int) $module->id;
$panelId = $id . '-panel';
$renderPosition = static function (array $position): string {
    $name = htmlspecialchars($position['name'], ENT_QUOTES, 'UTF-8');
    $image = htmlspecialchars($position['image'], ENT_QUOTES, 'UTF-8');
    $url = htmlspecialchars($position['url'], ENT_QUOTES, 'UTF-8');
    $title = $position['url'] !== '' ? '<a href="' . $url . '">' . $name . '</a>' : '<span>' . $name . '</span>';
    return '<li class="fdshop-cart-module__item" data-cart-module-position="' . htmlspecialchars($position['type'], ENT_QUOTES, 'UTF-8') . '"><img src="' . $image . '" alt="" width="64" height="64" loading="lazy"><div><strong><span>' . htmlspecialchars($position['quantity'], ENT_QUOTES, 'UTF-8') . ' × </span>' . $title . '</strong></div><b>' . htmlspecialchars($position['total'], ENT_QUOTES, 'UTF-8') . '</b></li>';
};
?>
<div id="<?php echo $id; ?>" class="fdshop-cart-module<?php echo htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8'); ?>" data-fdshop-cart-module data-summary-url="<?php echo htmlspecialchars($summaryUrl, ENT_QUOTES, 'UTF-8'); ?>">
  <button type="button" class="fdshop-cart-module__trigger" data-cart-module-open aria-controls="<?php echo $panelId; ?>" aria-expanded="false" aria-label="Warenkorb öffnen, <?php echo htmlspecialchars($summary['countFormatted'], ENT_QUOTES, 'UTF-8'); ?> Artikel">
    <span class="fa-solid fa-cart-shopping" aria-hidden="true"></span><span class="fdshop-cart-module__badge" data-cart-module-count><?php echo htmlspecialchars($summary['countFormatted'], ENT_QUOTES, 'UTF-8'); ?></span>
  </button>
  <div class="fdshop-cart-module__backdrop" data-cart-module-backdrop hidden></div>
  <aside id="<?php echo $panelId; ?>" class="fdshop-cart-module__panel" data-cart-module-panel role="dialog" aria-modal="true" aria-labelledby="<?php echo $panelId; ?>-title" tabindex="-1" hidden>
    <header><h2 id="<?php echo $panelId; ?>-title">Produkte im Warenkorb</h2><button type="button" data-cart-module-close aria-label="Warenkorb schließen">×</button></header>
    <div class="fdshop-cart-module__body"><p data-cart-module-empty<?php echo $summary['empty'] ? '' : ' hidden'; ?>>Dein Warenkorb ist noch leer.</p><ul data-cart-module-items><?php foreach ($summary['positions'] as $position) echo $renderPosition($position); ?></ul></div>
    <footer><div><span><strong data-cart-module-footer-count><?php echo htmlspecialchars($summary['countFormatted'], ENT_QUOTES, 'UTF-8'); ?></strong> Artikel</span><span>Summe <strong data-cart-module-total><?php echo htmlspecialchars($summary['subtotal'], ENT_QUOTES, 'UTF-8'); ?></strong></span></div><a class="btn btn-primary" href="<?php echo htmlspecialchars($cartUrl, ENT_QUOTES, 'UTF-8'); ?>">Zum Warenkorb</a></footer>
  </aside>
</div>
