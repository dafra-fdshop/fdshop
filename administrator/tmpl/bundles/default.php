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

$user      = $this->getCurrentUser();
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
?>

<form action="<?php echo Route::_('index.php?option=com_fdshop&view=bundles'); ?>" method="post" name="adminForm" id="adminForm">
    <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

    <div class="table-responsive">
        <table class="table itemList" id="bundleList">
            <caption class="visually-hidden">
                Bundleliste
            </caption>
            <thead>
                <tr>
                    <td class="w-1 text-center">
                        <?php echo HTMLHelper::_('grid.checkall'); ?>
                    </td>
                    <th scope="col" class="w-1 text-center">
                        <?php echo HTMLHelper::_('searchtools.sort', Text::_('JGRID_HEADING_ID'), 'a.id', $listDirn, $listOrder); ?>
                    </th>
                    <th scope="col">
                        <?php echo HTMLHelper::_('searchtools.sort', 'Bundle-Nummer', 'a.bundle_number', $listDirn, $listOrder); ?>
                    </th>
                    <th scope="col">
                        <?php echo HTMLHelper::_('searchtools.sort', 'Bundle-Name', 'a.bundle_name', $listDirn, $listOrder); ?>
                    </th>
                    <th scope="col">
                        Beschreibung
                    </th>
                    <th scope="col" class="w-1 text-center">
                        <?php echo HTMLHelper::_('searchtools.sort', 'Veröffentlicht', 'a.is_active', $listDirn, $listOrder); ?>
                    </th>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($this->items)) : ?>
                    <?php foreach ($this->items as $i => $item) : ?>
                        <?php
                        $editLink  = Route::_('index.php?option=com_fdshop&task=bundle.edit&id=' . (int) $item->id);
                        $canEdit   = $user->authorise('core.edit', 'com_fdshop');
                        $canChange = $user->authorise('core.edit.state', 'com_fdshop');
                        ?>
                        <tr class="row<?php echo $i % 2; ?>">
                            <td class="text-center">
                                <?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?>
                            </td>

                            <td class="text-center">
                                <?php echo (int) $item->id; ?>
                            </td>

                            <th scope="row">
                                <?php if ($canEdit) : ?>
                                    <a href="<?php echo $editLink; ?>">
                                        <?php echo $this->escape((string) ($item->bundle_number ?? '')); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo $this->escape((string) ($item->bundle_number ?? '')); ?>
                                <?php endif; ?>
                            </th>

                            <td>
                                <?php echo $this->escape((string) ($item->bundle_name ?? '')); ?>

                                <?php if (!empty($item->alias)) : ?>
                                    <div class="small text-muted">
                                        <?php echo $this->escape((string) $item->alias); ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php echo nl2br($this->escape((string) ($item->description ?? ''))); ?>
                            </td>

                            <td class="text-center">
                                <?php echo HTMLHelper::_('jgrid.published', (int) $item->is_active, $i, 'bundles.', $canChange, 'cb'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6" class="text-center">
                            <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>

            <?php if (!empty($this->items)) : ?>
                <tfoot>
                    <tr>
                        <td colspan="6">
                            <?php echo $this->pagination->getListFooter(); ?>
                        </td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>

    <?php echo $this->filterForm->renderControlFields(); ?>
</form>
