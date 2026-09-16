<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Administrator\Extension\FdshopComponent;
use FDShop\Component\FDShop\Administrator\Service\CategoryService;
use FDShop\Component\FDShop\Administrator\Service\CategoryServiceInterface;
use FDShop\Component\FDShop\Administrator\Service\CouponService;
use FDShop\Component\FDShop\Administrator\Service\CouponServiceInterface;
use FDShop\Component\FDShop\Administrator\Service\ManufacturerService;
use FDShop\Component\FDShop\Administrator\Service\ManufacturerServiceInterface;
use FDShop\Component\FDShop\Administrator\Service\BundleService;
use FDShop\Component\FDShop\Administrator\Service\BundleServiceInterface;
use FDShop\Component\FDShop\Administrator\Service\ProductService;
use FDShop\Component\FDShop\Administrator\Service\ProductServiceInterface;
use FDShop\Component\FDShop\Administrator\Service\PackagingService;
use FDShop\Component\FDShop\Administrator\Service\OrderService;
use FDShop\Component\FDShop\Administrator\Service\OrderServiceInterface;
use FDShop\Component\FDShop\Administrator\Service\FilterService;
use FDShop\Component\FDShop\Administrator\Service\FilterServiceInterface;
use FDShop\Component\FDShop\Site\Service\CartService;
use FDShop\Component\FDShop\Site\Service\CartServiceInterface;
use FDShop\Component\FDShop\Site\Service\BundleService as SiteBundleService;
use FDShop\Component\FDShop\Site\Service\BundleServiceInterface as SiteBundleServiceInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
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

        $container->registerServiceProvider(
            new RouterFactory('FDShop\\Component\\FDShop')
        );

        $container->set(
            CategoryServiceInterface::class,
            function (Container $container): CategoryServiceInterface {
                return new CategoryService(
                    $container->get(MVCFactoryInterface::class),
                    $container->get(DatabaseInterface::class)
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
            CouponServiceInterface::class,
            function (Container $container): CouponServiceInterface {
                return new CouponService(
                    $container->get(MVCFactoryInterface::class),
                    $container->get(DatabaseInterface::class)
                );
            }
        );

        $container->set(
            CouponService::class,
            function (Container $container): CouponService {
                return $container->get(CouponServiceInterface::class);
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
            PackagingService::class,
            static fn (): PackagingService => new PackagingService()
        );

        $container->set(FilterServiceInterface::class, fn (Container $container): FilterServiceInterface => new FilterService($container->get(DatabaseInterface::class)));
        $container->set(FilterService::class, fn (Container $container): FilterService => $container->get(FilterServiceInterface::class));

        $container->set(
            ProductServiceInterface::class,
            function (Container $container): ProductServiceInterface {
                return new ProductService(
                    $container->get(MVCFactoryInterface::class),
                    $container->get(DatabaseInterface::class),
                    $container->get(PackagingService::class)
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
            BundleServiceInterface::class,
            function (Container $container): BundleServiceInterface {
                return new BundleService(
                    $container->get(MVCFactoryInterface::class),
                    $container->get(DatabaseInterface::class)
                );
            }
        );

        $container->set(
            BundleService::class,
            function (Container $container): BundleService {
                return $container->get(BundleServiceInterface::class);
            }
        );
		
		$container->set(
			OrderServiceInterface::class,
			function (Container $container): OrderServiceInterface {
				return new OrderService(
					$container->get(DatabaseInterface::class)
				);
			}
		);

		$container->set(
			OrderService::class,
			function (Container $container): OrderService {
				return $container->get(OrderServiceInterface::class);
			}
		);

        $container->set(
            SiteBundleServiceInterface::class,
            function (Container $container): SiteBundleServiceInterface {
                return new SiteBundleService($container->get(DatabaseInterface::class));
            }
        );

        $container->set(
            SiteBundleService::class,
            function (Container $container): SiteBundleService {
                return $container->get(SiteBundleServiceInterface::class);
            }
        );

        $container->set(
            CartServiceInterface::class,
            function (Container $container): CartServiceInterface {
                return new CartService($container->get(DatabaseInterface::class), $container->get(PackagingService::class), $container->get(SiteBundleServiceInterface::class));
            }
        );

        $container->set(
            CartService::class,
            function (Container $container): CartService {
                return $container->get(CartServiceInterface::class);
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

				$component->setRouterFactory(
					$container->get(RouterFactoryInterface::class)
				);

				$component->setContainer($container);

				return $component;
			}
		);
    }
};
