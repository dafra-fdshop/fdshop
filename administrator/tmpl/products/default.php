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
use Joomla\CMS\Uri\Uri;

HTMLHelper::_('bootstrap.tooltip');

$user      = $this->getCurrentUser();
$userId    = (int) $user->id;
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$saveOrder = $listOrder === 'a.ordering';

if ($saveOrder) {
    HTMLHelper::_('draggablelist.draggable');
}
?>

<form action="<?php echo Route::_('index.php?option=com_fdshop&view=products'); ?>" method="post" name="adminForm" id="adminForm">
    <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

    <div class="table-responsive">
        <table class="table itemList" id="productList">
            <caption class="visually-hidden">
                <?php echo Text::_('COM_FDSHOP_PRODUCTS_TABLE_CAPTION'); ?>,
                <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
                <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
            </caption>
            <thead>
                <tr>
                    <td class="w-1 text-center">
                        <?php echo HTMLHelper::_('grid.checkall'); ?>
                    </td>
                    <th scope="col">
                        <?php echo HTMLHelper::_(
                            'searchtools.sort',
                            Text::_('Produktname'),
                            'a.product_name',
                            $listDirn,
                            $listOrder
                        ); ?>
                    </th>
                    <th scope="col" class="text-center">
                        <?php echo Text::_('Bild'); ?>
                    </th>
                    <th scope="col">
                        <?php echo HTMLHelper::_(
                            'searchtools.sort',
                            Text::_('Artikelnr.'),
                            'a.sku',
                            $listDirn,
                            $listOrder
                        ); ?>
                    </th>
                    <th scope="col">
                        <?php echo HTMLHelper::_(
                            'searchtools.sort',
                            Text::_('Verkaufspreiß'),
                            'a.active_price_gross',
                            $listDirn,
                            $listOrder
                        ); ?>
                    </th>
                    <th scope="col">
                        <?php echo Text::_('Kategorien'); ?>
                    </th>
                    <th scope="col">
                        <?php echo HTMLHelper::_(
                            'searchtools.sort',
                            Text::_('Hersteller'),
                            'm.manufacturer_name',
                            $listDirn,
                            $listOrder
                        ); ?>
                    </th>
                    <th scope="col" class="w-1 text-center">
                        <?php echo HTMLHelper::_(
                            'searchtools.sort',
                            Text::_('JPUBLISHED'),
                            'a.state',
                            $listDirn,
                            $listOrder
                        ); ?>
                    </th>
                    <th scope="col" class="w-1 text-center">
                        <?php echo HTMLHelper::_(
                            'searchtools.sort',
                            Text::_('JGRID_HEADING_ID'),
                            'a.id',
                            $listDirn,
                            $listOrder
                        ); ?>
                    </th>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($this->items)) : ?>
                    <?php foreach ($this->items as $i => $item) : ?>
                        <?php
                        $editLink   = Route::_('index.php?option=com_fdshop&task=product.edit&id=' . (int) $item->id);
                        $canEdit    = $user->authorise('core.edit', 'com_fdshop');
                        $canChange  = $user->authorise('core.edit.state', 'com_fdshop');
                        $imageSrc   = !empty($item->image_path_mobile) ? Uri::root() . ltrim((string) $item->image_path_mobile, '/') : '';
                        $categories = trim((string) ($item->category_names ?? ''));
                        ?>
                        <tr class="row<?php echo $i % 2; ?>">
                            <td class="text-center">
                                <?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?>
                            </td>

                            <th scope="row">
                                <?php if ($canEdit) : ?>
                                    <a href="<?php echo $editLink; ?>">
                                        <?php echo $this->escape((string) $item->product_name); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo $this->escape((string) $item->product_name); ?>
                                <?php endif; ?>

                                <?php if (!empty($item->alias)) : ?>
                                    <div class="small text-muted">
                                        <?php echo $this->escape((string) $item->alias); ?>
                                    </div>
                                <?php endif; ?>
                            </th>

                            <td class="text-center">
                                <?php if ($imageSrc !== '') : ?>
                                    <img
                                        src="<?php echo htmlspecialchars($imageSrc, ENT_QUOTES, 'UTF-8'); ?>"
                                        alt=""
                                        style="max-width: 60px; max-height: 60px;"
                                    >
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php echo $this->escape((string) $item->sku); ?>
                            </td>

                            <td>
                                <?php echo number_format((float) ($item->active_price_gross ?? 0), 2, ',', '.') . ' €'; ?>
                            </td>

                            <td>
                                <?php if ($categories !== '') : ?>
                                    <?php echo nl2br($this->escape(str_replace(', ', "\n", $categories))); ?>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php echo $this->escape((string) ($item->manufacturer_name ?? '')); ?>
                            </td>

                            <td class="text-center">
                                <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'products.', $canChange, 'cb'); ?>
                            </td>

                            <td class="text-center">
                                <?php echo (int) $item->id; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="9" class="text-center">
                            <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>

            <?php if (!empty($this->items)) : ?>
                <tfoot>

                    <tr>
                        <td colspan="9">
                            <?php echo $this->pagination->getListFooter(); ?>
                        </td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>

    <?php echo $this->filterForm->renderControlFields(); ?>
</form>