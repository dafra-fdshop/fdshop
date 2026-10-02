<?php
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
$query = trim((string) ($displayData['query'] ?? ''));
$id = 'fdshop-search-' . substr(md5((string) ($displayData['id'] ?? uniqid('', true))), 0, 8);
?>
<form class="fdshop-search" action="<?php echo Route::_('index.php?option=com_fdshop&view=search'); ?>" method="get" role="search" data-fdshop-search data-suggest-url="<?php echo Route::_('index.php?option=com_fdshop&task=search.suggest&format=json'); ?>">
<input type="hidden" name="option" value="com_fdshop"><input type="hidden" name="view" value="search"><label class="visually-hidden" for="<?php echo $id; ?>">Produkte suchen</label>
<div class="fdshop-search__field"><input id="<?php echo $id; ?>" class="form-control" type="search" name="q" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Produkt, Artikelnummer oder Hersteller suchen" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="<?php echo $id; ?>-list" data-fdshop-search-input><button type="submit" class="btn btn-primary">Suchen</button></div>
<div class="fdshop-search__suggestions" data-fdshop-search-suggestions hidden><ul id="<?php echo $id; ?>-list" role="listbox"></ul></div></form>
