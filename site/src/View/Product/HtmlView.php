<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Site\View\Product;

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Helper\RouteHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Uri\Uri;

final class HtmlView extends BaseHtmlView
{
    public object $item;
    public string $categoryUrl;
    public array $images = [];
    public string $placeholderImage;

    public function display($tpl = null): void
    {
        $item = $this->getModel()->getItem();

        if ($item === null) {
            throw new \RuntimeException('Das gewählte FDShop-Produkt wurde nicht gefunden.', 404);
        }

        $this->item = $item;
        $categoryId = max(0, Factory::getApplication()->getInput()->getInt('catid'));
        $this->categoryUrl = $categoryId > 0 ? RouteHelper::getCategoryRoute($categoryId) : '';
        $root = rtrim(Uri::root(true), '/');
        $this->placeholderImage = $root . '/media/com_fdshop/images/product-placeholder.svg';
        $this->images = array_map(static fn (string $path): string => $root . '/' . ltrim($path, '/'), $item->media['images']);
        $item->manufacturer_url = $item->manufacturer_id
            ? RouteHelper::getManufacturerRoute((int) $item->manufacturer_id, (string) $item->manufacturer_alias)
            : '';
        $item->price_formatted = $this->formatPrice((float) $item->current_price, (string) $item->currency);
        $item->regular_price_formatted = $this->formatPrice((float) $item->sale_price, (string) $item->currency);
        $item->stock_class = match ((string) $item->in_stock) {
            'Verfügbar', 'Bestellbar' => 'fdshop-stock--normal',
            'wenige Verfügbar', 'wenige Bestellbar' => 'fdshop-stock--low',
            default => 'fdshop-stock--none',
        };
        $item->physical_stock_text = 'Noch nicht im Lager';
        if ((int) $item->physically_in_stock === 1) {
            $item->physical_stock_text = 'Im Lager';
        } elseif (!empty($item->available_from)) {
            $item->physical_stock_text = 'Verfügbar ab ' . Factory::getDate((string) $item->available_from)->format('d.m.Y', true);
        }

        $document = Factory::getApplication()->getDocument();
        $document->setTitle(trim((string) $item->meta_title) ?: (string) $item->product_name);
        if (trim((string) $item->meta_description) !== '') {
            $document->setDescription((string) $item->meta_description);
        }
        if (trim((string) $item->meta_keywords) !== '') {
            $document->setMetaData('keywords', (string) $item->meta_keywords);
        }

        $document->getWebAssetManager()->useStyle('com_fdshop.site')->useScript('com_fdshop.site');
        parent::display($tpl);
    }

    private function formatPrice(float $price, string $currency): string
    {
        return number_format($price, 2, ',', '.') . ' ' . strtoupper(trim($currency ?: 'EUR'));
    }
}
