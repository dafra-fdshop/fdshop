<?php
defined('_JEXEC') or die;

use FDShop\Plugin\User\FDShopProfile\Extension\FDShopProfile;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class, $container->lazy(FDShopProfile::class, function (Container $container) {
            $plugin = new FDShopProfile((array) PluginHelper::getPlugin('user', 'fdshopprofile'));
            $plugin->setApplication(Factory::getApplication());
            $plugin->setDatabase($container->get(DatabaseInterface::class));
            return $plugin;
        }));
    }
};
