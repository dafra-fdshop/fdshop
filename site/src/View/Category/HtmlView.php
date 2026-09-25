<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Site\View\Category;

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Helper\PurchaseHelper;
use FDShop\Component\FDShop\Site\Helper\ProductCardHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
    public object $category;
    public array $items = [];
    public object $pagination;
    public object $state;
    public array $sortOptions = [];
    public array $limitOptions = [12, 24, 36, 48];
    public bool $purchaseEnabled = false;
    public array $filterFacets = [];
    public array $activeFilters = [];
    public array $filterChips = [];
    public bool $filterUiEnabled = false;

    public function display($tpl = null): void
    {
        $model = $this->getModel();
        $category = $model->getCategory();

        if ($category === null) {
            throw new \RuntimeException('Die gewählte FDShop-Kategorie wurde nicht gefunden.', 404);
        }

        $this->category = $category;
        $this->items = $model->getItems();
        $this->pagination = $model->getPagination();
        $this->state = $model->getState();
        $this->purchaseEnabled = PurchaseHelper::isShopEnabled();
        $this->filterFacets = $model->getFilterFacets();
        $this->activeFilters = (array) $this->state->get('filter.fdshop', []);
        $this->filterChips = $model->getFilterChips();
        $this->filterUiEnabled = $model->hasAssignedFilterModule();
        $this->sortOptions = [
            'name:asc'   => 'Name aufsteigend',
            'name:desc'  => 'Name absteigend',
            'price:asc'  => 'Preis aufsteigend',
            'price:desc' => 'Preis absteigend',
        ];

        foreach ($this->items as $item) {
            ProductCardHelper::prepare($item, (int) $category->id);
        }

        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->useStyle('com_fdshop.site')
            ->useScript('com_fdshop.site')
            ->useScript('com_fdshop.purchase');

        parent::display($tpl);
    }

}
