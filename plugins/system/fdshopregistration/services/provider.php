<?php
defined('_JEXEC') or die;

use FDShop\Plugin\System\FDShopRegistration\Extension\FDShopRegistration;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class, $container->lazy(FDShopRegistration::class, function () {
            $plugin = new FDShopRegistration((array) PluginHelper::getPlugin('system', 'fdshopregistration'));
            $plugin->setApplication(Factory::getApplication());
            return $plugin;
        }));
    }
};
