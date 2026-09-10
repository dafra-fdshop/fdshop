<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
?>
<dialog class="fdshop-purchase-modal" data-purchase-modal aria-labelledby="fdshop-purchase-modal-title">
    <div class="fdshop-purchase-modal__body">
        <button type="button" class="fdshop-purchase-modal__close" data-purchase-close aria-label="Meldung schließen">×</button>
        <h2 id="fdshop-purchase-modal-title" data-purchase-title>Zum Warenkorb hinzugefügt</h2>
        <p data-purchase-message></p>
        <dl><div><dt>Produkt</dt><dd data-purchase-product></dd></div><div><dt>Menge</dt><dd data-purchase-effective></dd></div><div><dt>Einzelpreis</dt><dd data-purchase-price></dd></div><div><dt>Betrag</dt><dd data-purchase-amount></dd></div></dl>
        <div class="fdshop-purchase-modal__actions"><a class="btn btn-primary" href="#" data-purchase-cart>Zum Warenkorb</a><button type="button" class="btn btn-outline-secondary" data-purchase-close>Weiter einkaufen</button></div>
    </div>
</dialog>
<form hidden data-purchase-token><?php echo HTMLHelper::_('form.token'); ?></form>
