<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Products;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

class HtmlView extends BaseHtmlView
{
    public $items = [];

    public $state;

    public $pagination;

    public $filterForm;

    public $activeFilters = [];

    public bool $isTrash = false;

    public function display($tpl = null)
    {
        $model = $this->getModel();

        $this->items         = $model->getItems();
        $this->state         = $model->getState();
        $this->pagination    = $model->getPagination();
        $this->filterForm    = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();
        $this->isTrash       = (int) $this->state->get('filter.deleted', 0) === 1;

        if (is_object($this->filterForm)) {
            $this->filterForm
                ->addControlField('task', '')
                ->addControlField('boxchecked', '0');
        }

        ToolbarHelper::title($this->isTrash ? 'FDShop - Produkte: Papierkorb' : 'FDShop - Produkte');

        if ($this->isTrash) {
            if ($this->getCurrentUser()->authorise('core.delete', 'com_fdshop')) {
                ToolbarHelper::custom('products.restore', 'refresh', '', 'Wiederherstellen', true);
                ToolbarHelper::deleteList(
                    'Ausgewählte Produkte wirklich endgültig löschen?',
                    'products.deletePermanently',
                    'Endgültig löschen'
                );
            }
        } else {
            ToolbarHelper::addNew('product.add');

            if ($this->getCurrentUser()->authorise('core.delete', 'com_fdshop')) {
                ToolbarHelper::deleteList(
                    'Ausgewählte Produkte in den Papierkorb verschieben?',
                    'products.delete',
                    'JTOOLBAR_TRASH'
                );
            }
        }

        parent::display($tpl);
    }
}
