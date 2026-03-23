<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Administrator\Extension\FdshopComponent;
use FDShop\Component\FDShop\Administrator\Service\CategoryService;
use FDShop\Component\FDShop\Administrator\Service\CategoryServiceInterface;
use FDShop\Component\FDShop\Administrator\Service\ManufacturerService;
use FDShop\Component\FDShop\Administrator\Service\ManufacturerServiceInterface;
use FDShop\Component\FDShop\Administrator\Service\ProductService;
use FDShop\Component\FDShop\Administrator\Service\ProductServiceInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(
            new ComponentDispatcherFactory('FDShop\\Component\\FDShop')
        );

        $container->registerServiceProvider(
            new MVCFactory('FDShop\\Component\\FDShop')
        );

        $container->set(
            CategoryServiceInterface::class,
            function (Container $container): CategoryServiceInterface {
                return new CategoryService(
                    $container->get(MVCFactoryInterface::class)
                );
            }
        );

        $container->set(
            CategoryService::class,
            function (Container $container): CategoryService {
                return $container->get(CategoryServiceInterface::class);
            }
        );

        $container->set(
            ManufacturerServiceInterface::class,
            function (Container $container): ManufacturerServiceInterface {
                return new ManufacturerService(
                    $container->get(MVCFactoryInterface::class),
                    $container->get(DatabaseInterface::class)
                );
            }
        );

        $container->set(
            ManufacturerService::class,
            function (Container $container): ManufacturerService {
                return $container->get(ManufacturerServiceInterface::class);
            }
        );

        $container->set(
            ProductServiceInterface::class,
            function (Container $container): ProductServiceInterface {
                return new ProductService(
                    $container->get(MVCFactoryInterface::class),
                    $container->get(DatabaseInterface::class)
                );
            }
        );

        $container->set(
            ProductService::class,
            function (Container $container): ProductService {
                return $container->get(ProductServiceInterface::class);
            }
        );

        $container->set(
            ComponentInterface::class,
            function (Container $container): ComponentInterface {
                $component = new FdshopComponent(
                    $container->get(ComponentDispatcherFactoryInterface::class)
                );

                $component->setMVCFactory(
                    $container->get(MVCFactoryInterface::class)
                );

                return $component;
            }
        );
    }
};