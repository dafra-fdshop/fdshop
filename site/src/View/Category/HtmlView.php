<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Site\View\Category;

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Helper\RouteHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Uri\Uri;

final class HtmlView extends BaseHtmlView
{
    public object $category;
    public array $items = [];
    public object $pagination;
    public object $state;
    public array $sortOptions = [];
    public array $limitOptions = [12, 24, 36, 48];

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
        $this->sortOptions = [
            'name:asc'   => 'Name aufsteigend',
            'name:desc'  => 'Name absteigend',
            'price:asc'  => 'Preis aufsteigend',
            'price:desc' => 'Preis absteigend',
        ];

        $root = rtrim(Uri::root(true), '/');
        $placeholder = $root . '/media/com_fdshop/images/product-placeholder.svg';
        $stockClasses = [
            'Verfügbar'          => 'fdshop-stock--normal',
            'Bestellbar'         => 'fdshop-stock--normal',
            'wenige Verfügbar'   => 'fdshop-stock--low',
            'wenige Bestellbar'  => 'fdshop-stock--low',
            'Ausverkauft'        => 'fdshop-stock--none',
        ];

        foreach ($this->items as $item) {
            $item->detail_url = RouteHelper::getProductRoute((int) $item->id, (int) $category->id);
            $item->image_url = $item->media['image']
                ? $root . '/' . ltrim((string) $item->media['image'], '/')
                : $placeholder;
            $item->price_formatted = $this->formatPrice((float) $item->current_price, (string) $item->currency);
            $item->regular_price_formatted = $this->formatPrice((float) $item->sale_price, (string) $item->currency);
            $item->stock_class = $stockClasses[(string) $item->in_stock] ?? 'fdshop-stock--none';
        }

        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->useStyle('com_fdshop.site')
            ->useScript('com_fdshop.site');

        parent::display($tpl);
    }

    private function formatPrice(float $price, string $currency): string
    {
        return number_format($price, 2, ',', '.') . ' ' . strtoupper(trim($currency ?: 'EUR'));
    }
}
