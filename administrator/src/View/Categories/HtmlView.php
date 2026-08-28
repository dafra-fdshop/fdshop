<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Categories;

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

    public function display($tpl = null)
    {
        $model = $this->getModel();

        $this->items         = $model->getItems();
        $this->state         = $model->getState();
        $this->pagination    = $model->getPagination();
        $this->filterForm    = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();

        if (is_object($this->filterForm)) {
            $this->filterForm
                ->addControlField('task', '')
                ->addControlField('boxchecked', '0');
        }

        ToolbarHelper::title('FDShop - Kategorien');
        ToolbarHelper::addNew('category.add');

        if ($this->getCurrentUser()->authorise('core.delete', 'com_fdshop')) {
            ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'categories.delete', 'JTOOLBAR_DELETE');
        }

        parent::display($tpl);
    }
}
