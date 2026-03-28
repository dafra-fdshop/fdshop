<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Products;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

class HtmlView extends BaseHtmlView
{
    protected $items = [];

    protected $state;

    protected $pagination;

    protected $filterForm;

    protected $activeFilters = [];

    public function display($tpl = null)
    {
        $model = $this->getModel();

        $this->items         = $model->getItems();
        $this->state         = $model->getState();
        $this->pagination    = $model->getPagination();
        $this->filterForm    = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();

        $this->attachProductImages();

        ToolbarHelper::title('FDShop - Produkte');
        ToolbarHelper::addNew('product.add');

        parent::display($tpl);
    }

    private function attachProductImages(): void
    {
        if (empty($this->items)) {
            return;
        }

        $productIds = [];

        foreach ($this->items as $item) {
            $productIds[] = (int) $item->id;
            $item->image_path_mobile = null;
        }

        $productIds = array_values(array_unique(array_filter($productIds)));

        if (empty($productIds)) {
            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('product_id'),
                $db->quoteName('path_mobile'),
            ])
            ->from($db->quoteName('#__fdshop_media'))
            ->where($db->quoteName('product_id') . ' IN (' . implode(',', $productIds) . ')')
            ->where($db->quoteName('path_mobile') . " <> " . $db->quote(''))
            ->order(
                $db->quoteName('product_id') . ' ASC, '
                . $db->quoteName('is_primary') . ' DESC, '
                . $db->quoteName('ordering') . ' ASC, '
                . $db->quoteName('id') . ' ASC'
            );

        $db->setQuery($query);
        $rows = (array) $db->loadObjectList();

        $imageMap = [];

        foreach ($rows as $row) {
            $productId = (int) $row->product_id;

            if (!isset($imageMap[$productId])) {
                $imageMap[$productId] = (string) $row->path_mobile;
            }
        }

        foreach ($this->items as $item) {
            $item->image_path_mobile = $imageMap[(int) $item->id] ?? null;
        }
    }
}