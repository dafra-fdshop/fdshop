<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('behavior.multiselect');
HTMLHelper::_('draggablelist.draggable');
?>

<form action="index.php?option=com_fdshop&view=categories" method="post" name="adminForm" id="adminForm">

<table class="table table-striped" id="categoryList">
    <thead>
        <tr>
            <th width="1%">#</th>
            <th width="1%"><?php echo HTMLHelper::_('grid.checkall'); ?></th>
            <th width="5%">Order</th>
            <th>Name</th>
            <th>Alias</th>
            <th>Aktiv</th>
            <th>ID</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($this->items as $i => $item) : ?>
            <tr>
                <td><?php echo $i + 1; ?></td>

                <td>
                    <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                </td>

                <td class="order nowrap">
                    <?php echo HTMLHelper::_('grid.order', $this->items, 'filesave.png', 'categories.saveorder'); ?>
                </td>

                <td>
                    <a href="index.php?option=com_fdshop&view=category&layout=edit&id=<?php echo $item->id; ?>">
                        <?php echo $this->escape($item->category_name); ?>
                    </a>
                </td>

                <td><?php echo $this->escape($item->alias); ?></td>

                <td>
                    <?php echo HTMLHelper::_('grid.published', $item->is_active, $i, 'categories.', true); ?>
                </td>

                <td><?php echo $item->id; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<input type="hidden" name="task" value="">
<?php echo HTMLHelper::_('form.token'); ?>
</form>