<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('bootstrap.tooltip');

$user = $this->getCurrentUser();
$listOrder = $this->state->get('list.ordering');
$listDirn = $this->state->get('list.direction');
?>

<form action="<?php echo Route::_('index.php?option=com_fdshop&view=storage_locations'); ?>" method="post" name="adminForm" id="adminForm">
    <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

    <div class="table-responsive">
        <table class="table itemList" id="storageLocationList">
            <caption class="visually-hidden">Lagerortliste</caption>
            <thead>
                <tr>
                    <td class="w-1 text-center"><?php echo HTMLHelper::_('grid.checkall'); ?></td>
                    <th scope="col" class="w-1 text-center">
                        <?php echo HTMLHelper::_('searchtools.sort', Text::_('JPUBLISHED'), 'a.is_active', $listDirn, $listOrder); ?>
                    </th>
                    <th scope="col">
                        <?php echo HTMLHelper::_('searchtools.sort', 'Lagerort', 'a.location_name', $listDirn, $listOrder); ?>
                    </th>
                    <th scope="col">
                        <?php echo HTMLHelper::_('searchtools.sort', 'Code', 'a.code', $listDirn, $listOrder); ?>
                    </th>
                    <th scope="col">Beschreibung</th>
                    <th scope="col" class="w-1 text-center">
                        <?php echo HTMLHelper::_('searchtools.sort', Text::_('JGRID_HEADING_ID'), 'a.id', $listDirn, $listOrder); ?>
                    </th>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($this->items)) : ?>
                    <?php foreach ($this->items as $i => $item) : ?>
                        <?php
                        $editLink = Route::_('index.php?option=com_fdshop&task=storage_location.edit&id=' . (int) $item->id);
                        $canEdit = $user->authorise('core.edit', 'com_fdshop');
                        $canChange = $user->authorise('core.edit.state', 'com_fdshop');
                        ?>
                        <tr class="row<?php echo $i % 2; ?>">
                            <td class="text-center">
                                <?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?>
                            </td>
                            <td class="text-center">
                                <?php echo HTMLHelper::_('jgrid.published', (int) $item->published, $i, 'storage_locations.', $canChange, 'cb'); ?>
                            </td>
                            <th scope="row">
                                <?php if ($canEdit) : ?>
                                    <a href="<?php echo $editLink; ?>">
                                        <?php echo $this->escape((string) $item->location_name); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo $this->escape((string) $item->location_name); ?>
                                <?php endif; ?>
                            </th>
                            <td><?php echo $this->escape((string) $item->code); ?></td>
                            <td><?php echo $this->escape((string) $item->description); ?></td>
                            <td class="text-center"><?php echo (int) $item->id; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6" class="text-center"><?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>

            <?php if (!empty($this->items)) : ?>
                <tfoot>
                    <tr>
                        <td colspan="6"><?php echo $this->pagination->getListFooter(); ?></td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>

    <?php echo $this->filterForm->renderControlFields(); ?>
</form>
