<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Site\View\Category;

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Helper\PurchaseHelper;
use FDShop\Component\FDShop\Site\Helper\ProductCardHelper;
use FDShop\Component\FDShop\Site\Helper\SearchHelper;
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
    public bool $showSearch = false;

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
        $paginationState = [
            'option' => 'com_fdshop',
            'view' => 'category',
            'id' => (int) $category->id,
            'sort' => (string) $this->state->get('filter.sort', 'name'),
            'dir' => (string) $this->state->get('filter.direction', 'asc'),
            'limit' => (int) $this->state->get('list.limit', 24),
            'fd_filter' => (array) $this->state->get('filter.fdshop', []),
        ];

        foreach ($paginationState as $key => $value) {
            $this->pagination->setAdditionalUrlParam($key, $value);
        }
        $this->purchaseEnabled = PurchaseHelper::isShopEnabled();
        $this->filterFacets = $model->getFilterFacets();
        $this->activeFilters = (array) $this->state->get('filter.fdshop', []);
        $this->filterChips = $model->getFilterChips();
        $this->filterUiEnabled = $model->hasAssignedFilterModule();
        $searchConfig = SearchHelper::config();
        $this->showSearch = (int) $searchConfig->search_active === 1 && (int) $searchConfig->search_category_active === 1;
        $this->sortOptions = [
            'name:asc'   => 'Name aufsteigend',
            'name:desc'  => 'Name absteigend',
            'price:asc'  => 'Preis aufsteigend',
            'price:desc' => 'Preis absteigend',
        ];

        foreach ($this->items as $item) {
            ProductCardHelper::prepare($item, (int) $category->id);
        }

        $assets = Factory::getApplication()->getDocument()->getWebAssetManager()
            ->useStyle('com_fdshop.site')
            ->useScript('com_fdshop.site')
            ->useScript('com_fdshop.purchase')
            ->useScript('com_fdshop.favorites')
            ->useScript('com_fdshop.comparison');

        if ($this->showSearch) {
            $assets->useScript('com_fdshop.search');
        }

        parent::display($tpl);
    }

}
