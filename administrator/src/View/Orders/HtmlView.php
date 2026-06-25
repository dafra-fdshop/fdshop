<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Orders;

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

    public $statusOptions = [];

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

            $statusField = $this->filterForm->getField('status', 'filter');

            if (is_object($statusField) && method_exists($statusField, 'getOptions')) {
                $this->statusOptions = $model->getStatusOptions();
            }
        }

        ToolbarHelper::title('FDShop - Bestellungen');
        ToolbarHelper::custom('orders.save', 'save', '', 'JTOOLBAR_SAVE', true);
		ToolbarHelper::custom('orders.trashconfirm', 'trash', '', 'JTOOLBAR_TRASH', true);
		
		$stateFilter = $this->state->get('filter.state', '');

		if ((string) $stateFilter === '-2') {
			ToolbarHelper::custom('orders.restore', 'refresh', '', 'Wiederherstellen', true);
		}
		

        parent::display($tpl);
    }
}