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

final class HtmlView extends BaseHtmlView
{
    public object $item;
    public string $categoryUrl;

    public function display($tpl = null): void
    {
        $item = $this->getModel()->getItem();

        if ($item === null) {
            throw new \RuntimeException('Das gewählte FDShop-Produkt wurde nicht gefunden.', 404);
        }

        $this->item = $item;
        $categoryId = max(0, Factory::getApplication()->getInput()->getInt('catid'));
        $this->categoryUrl = $categoryId > 0 ? RouteHelper::getCategoryRoute($categoryId) : '';

        Factory::getApplication()->getDocument()->getWebAssetManager()->useStyle('com_fdshop.site');
        parent::display($tpl);
    }
}
