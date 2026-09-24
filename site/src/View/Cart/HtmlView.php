<?php

namespace FDShop\Component\FDShop\Site\View\Cart;

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Helper\RouteHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Uri\Uri;

final class HtmlView extends BaseHtmlView
{
    public array $cart = [];
    public object $config;
    public string $placeholderImage;
    public string $submissionId;

    public function display($tpl = null): void
    {
        $model = $this->getModel();
        $this->cart = $model->getCartData();
        $this->config = $model->getConfig();
        $this->placeholderImage = rtrim(Uri::root(true), '/') . '/media/com_fdshop/images/product-placeholder.svg';
        $this->submissionId = $this->uuid();

        foreach ($this->cart['items'] as $item) {
                $item->product_url = RouteHelper::getProductRoute((int) $item->product_id, (int) $item->category_id);
                $item->image_url = $item->image !== '' ? rtrim(Uri::root(true), '/') . '/' . ltrim($item->image, '/') : $this->placeholderImage;
                $item->quantity_formatted = $this->formatQuantity((float) $item->quantity);
                $item->unit_price_formatted = $this->formatPrice((float) $item->unit_price, (string) $item->currency);
                $item->regular_price_formatted = $this->formatPrice((float) $item->sale_price, (string) $item->currency);
                $item->line_total_formatted = $this->formatPrice((float) $item->line_total, (string) $item->currency);
        }

        Factory::getApplication()->getDocument()->setTitle('Warenkorb');
        Factory::getApplication()->getDocument()->getWebAssetManager()->useStyle('com_fdshop.site')->useScript('com_fdshop.cart');
        parent::display($tpl);
    }

    public function formatPrice(float $price, string $currency = 'EUR'): string
    {
        $symbol = strtoupper($currency) === 'EUR' ? '€' : strtoupper($currency);
        return number_format($price, 2, ',', '.') . ' ' . $symbol;
    }

    private function formatQuantity(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.');
    }

    private function uuid(): string
    {
        $bytes=random_bytes(16); $bytes[6]=chr((ord($bytes[6])&0x0f)|0x40); $bytes[8]=chr((ord($bytes[8])&0x3f)|0x80); $hex=bin2hex($bytes);
        return substr($hex,0,8).'-'.substr($hex,8,4).'-'.substr($hex,12,4).'-'.substr($hex,16,4).'-'.substr($hex,20);
    }
}
