<?php
defined('_JEXEC') or die;
use Joomla\CMS\Router\Route;
?>
<div class="table-responsive"><table class="table itemList"><caption class="visually-hidden">Systemfilter</caption><thead><tr><th>Reihenfolge</th><th>Bezeichnung</th><th>Technischer Schlüssel</th><th>Aktiv</th><th>Bereiche</th></tr></thead><tbody>
<?php foreach($this->items as $item): ?><tr><td><?php echo (int)$item->ordering;?></td><th><a href="<?php echo Route::_('index.php?option=com_fdshop&task=filter.edit&id='.(int)$item->id);?>"><?php echo $this->escape($item->label);?></a></th><td><code><?php echo $this->escape($item->filter_key);?></code></td><td><?php echo (int)$item->is_active===1?'Ja':'Nein';?></td><td><?php echo (int)$item->range_count;?></td></tr><?php endforeach; ?>
</tbody></table></div>
