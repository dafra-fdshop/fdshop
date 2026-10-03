<?php

defined('_JEXEC') or die;

$count = (int) $state['count'];
$label = $state['guest'] ? 'Meine Favoriten öffnen, Anmeldung erforderlich' : 'Meine Favoriten öffnen, ' . $count . ' gespeichert';
?>
<div id="fdshop-favorites-module-<?php echo (int) $module->id; ?>" class="fdshop-favorites-module<?php echo htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8'); ?>" data-fdshop-favorites-module data-counter-url="<?php echo htmlspecialchars($counterUrl, ENT_QUOTES, 'UTF-8'); ?>">
  <a class="fdshop-favorites-module__trigger" href="<?php echo htmlspecialchars($favoritesUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>">
    <span class="fa-solid fa-heart" aria-hidden="true"></span>
    <span class="fdshop-favorites-module__badge" data-favorites-module-count><?php echo $count; ?></span>
  </a>
</div>
