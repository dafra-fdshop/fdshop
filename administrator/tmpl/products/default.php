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
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
?>

<form action="<?php echo Route::_('index.php?option=com_fdshop&view=products'); ?>" method="post" name="adminForm" id="adminForm">
    <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

    <div class="table-responsive">
        <table class="table itemList" id="productList">
            <caption class="visually-hidden">
                Produktliste
            </caption>
            <thead>
                <tr>
                    <td class="w-1 text-center">
                        <?php echo HTMLHelper::_('grid.checkall'); ?>
                    </td>
                    <th scope="col">
                        <?php echo HTMLHelper::_('searchtools.sort', 'Produktname', 'a.product_name', $listDirn, $listOrder); ?>
                    </th>
                    <th scope="col" class="text-center">
                        Bild
                    </th>
                    <th scope="col">
                        <?php echo HTMLHelper::_('searchtools.sort', 'Artikelnr.', 'd.sku', $listDirn, $listOrder); ?>
                    </th>
                    <th scope="col">
                        <?php echo HTMLHelper::_('searchtools.sort', 'Verkaufspreis', 'a.sale_price', $listDirn, $listOrder); ?>
                    </th>
                    <th scope="col">
                        <?php echo HTMLHelper::_('searchtools.sort', 'Bestand', 'a.in_stock', $listDirn, $listOrder); ?>
                    </th>
                    <th scope="col">
                        Kategorien
                    </th>
                    <th scope="col">
                        <?php echo HTMLHelper::_('searchtools.sort', 'Hersteller', 'm.manufacturer_name', $listDirn, $listOrder); ?>
                    </th>
                    <th scope="col" class="w-1 text-center">
                        <?php echo HTMLHelper::_('searchtools.sort', Text::_('JPUBLISHED'), 'a.is_active', $listDirn, $listOrder); ?>
                    </th>
                    <th scope="col" class="w-1 text-center">
                        <?php echo HTMLHelper::_('searchtools.sort', Text::_('JGRID_HEADING_ID'), 'a.id', $listDirn, $listOrder); ?>
                    </th>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($this->items)) : ?>
                    <?php foreach ($this->items as $i => $item) : ?>
                        <?php
                        $editLink   = Route::_('index.php?option=com_fdshop&task=product.edit&id=' . (int) $item->id);
                        $canEdit    = !$this->isTrash && $user->authorise('core.edit', 'com_fdshop');
                        $canChange  = !$this->isTrash && $user->authorise('core.edit.state', 'com_fdshop');
                        $imageSrc   = !empty($item->image_path_mobile) ? Uri::root() . ltrim((string) $item->image_path_mobile, '/') : '';
                        $categories = trim((string) ($item->category_names ?? ''));
                        $price      = number_format((float) ($item->sale_price ?? 0), 2, ',', '.');

                        if (!empty($item->currency)) {
                            $price .= ' ' . $this->escape((string) $item->currency);
                        }
                        ?>
                        <tr class="row<?php echo $i % 2; ?>">
                            <td class="text-center">
                                <?php echo HTMLHelper::_('grid.id', $i, (int) $item->id); ?>
                            </td>

                            <th scope="row">
                                <?php if ($canEdit) : ?>
                                    <a href="<?php echo $editLink; ?>">
                                        <?php echo $this->escape((string) ($item->product_name ?? '')); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo $this->escape((string) ($item->product_name ?? '')); ?>
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
                                <?php echo $this->escape((string) ($item->sku ?? '')); ?>
                            </td>

                            <td>
                                <?php echo $price; ?>
                            </td>

                            <td>
                                <?php echo $this->escape((string) ($item->in_stock ?? '')); ?>
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
                                <?php echo HTMLHelper::_('jgrid.published', $item->is_active, $i, 'products.', $canChange, 'cb'); ?>
                            </td>

                            <td class="text-center">
                                <?php echo (int) $item->id; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="10" class="text-center">
                            <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>

            <?php if (!empty($this->items)) : ?>
                <tfoot>
                    <tr>
                        <td colspan="10">
                            <?php echo $this->pagination->getListFooter(); ?>
                        </td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>

    <?php echo $this->filterForm->renderControlFields(); ?>
</form>
