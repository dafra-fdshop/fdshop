<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;

final class Router extends RouterView
{
    public function __construct(SiteApplication $app, AbstractMenu $menu)
    {
        $category = new RouterViewConfiguration('category');
        $category->setKey('id');
        $this->registerView($category);

        $product = new RouterViewConfiguration('product');
        $product->setKey('id')->setParent($category, 'catid');
        $this->registerView($product);

        $manufacturer = new RouterViewConfiguration('manufacturer');
        $manufacturer->setKey('id');
        $this->registerView($manufacturer);

        $cart = new RouterViewConfiguration('cart');
        $this->registerView($cart);

        parent::__construct($app, $menu);
        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    public function getCategorySegment($id, $query): array
    {
        return [(int) $id => (string) (int) $id];
    }

    public function getCategoryId($segment, $query): int|false
    {
        $id = (int) $segment;

        return $id > 0 ? $id : false;
    }

    public function getProductSegment($id, $query): array
    {
        return [(int) $id => (string) (int) $id];
    }

    public function getProductId($segment, $query): int|false
    {
        $id = (int) $segment;

        return $id > 0 ? $id : false;
    }

    public function getManufacturerSegment($id, $query): array
    {
        $manufacturerId = (int) $id;

        return $manufacturerId > 0 ? [$manufacturerId => (string) $id] : [];
    }

    public function getManufacturerId($segment, $query): int|false
    {
        $id = (int) $segment;

        return $id > 0 ? $id : false;
    }
}
