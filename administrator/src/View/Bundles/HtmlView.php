<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Bundles;

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

        ToolbarHelper::title('FDShop - Bundles');
        ToolbarHelper::addNew('bundle.add');
        ToolbarHelper::publish('bundles.publish', 'JTOOLBAR_PUBLISH', true);
        ToolbarHelper::unpublish('bundles.unpublish', 'JTOOLBAR_UNPUBLISH', true);
        ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'bundles.delete', 'JTOOLBAR_DELETE');

        parent::display($tpl);
    }
}
