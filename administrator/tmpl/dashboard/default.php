<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;

?>
<h1>FDShop</h1>
<div class="card"><div class="card-header"><strong>Offene Verfügbarkeitsbenachrichtigungen</strong></div><div class="card-body">
<?php if (empty($this->watchlist)) : ?><p class="mb-0">Aktuell gibt es keine aktiven Vormerkungen.</p><?php else : ?><table class="table"><thead><tr><th>Produkt</th><th>Wartende Kunden</th><th></th></tr></thead><tbody><?php foreach ($this->watchlist as $row) : ?><tr><td><?php echo htmlspecialchars((string)$row->product_name,ENT_QUOTES,'UTF-8'); ?></td><td><?php echo (int)$row->waiting_count; ?></td><td><a href="index.php?option=com_fdshop&view=product&layout=edit&id=<?php echo (int)$row->product_id; ?>#stock">Produkt öffnen</a></td></tr><?php endforeach; ?></tbody></table><?php endif; ?>
</div></div>
