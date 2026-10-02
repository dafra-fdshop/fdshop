<?php
defined('_JEXEC') or die;
use Joomla\CMS\Layout\LayoutHelper;
$q=(string)$this->state->get('search.query','');$total=(int)$this->pagination->total;
?>
<main class="fdshop-search-page"><header><h1>Produktsuche</h1><?php echo LayoutHelper::render('search.form',['query'=>$q,'id'=>'results'],JPATH_COMPONENT_SITE.'/layouts');?><p class="fdshop-search-page__summary"><?php echo $q===''?'Bitte gib einen Suchbegriff ein.':htmlspecialchars($total.' Treffer für „'.$q.'“',ENT_QUOTES,'UTF-8');?></p></header><?php if($q!==''&&!$this->items):?><div class="fdshop-search-page__empty"><strong>Keine Produkte gefunden.</strong><p>Versuche einen anderen Produktnamen, eine Artikelnummer oder einen Hersteller.</p></div><?php elseif($this->items):?><div class="fdshop-products"><?php foreach($this->items as $item)echo LayoutHelper::render('product.card',['item'=>$item,'purchaseEnabled'=>$this->purchaseEnabled],JPATH_COMPONENT_SITE.'/layouts');?></div><?php endif;?><?php if($total>(int)$this->state->get('list.limit',24)):?><nav class="fdshop-pagination"><?php echo$this->pagination->getPagesLinks();?></nav><?php endif;?></main>
