<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Extension\MVCComponent;
use Joomla\DI\Container;

final class FdshopComponent extends MVCComponent
{
    private ?Container $container = null;

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    public function getContainer(): Container
    {
        if ($this->container === null) {
            throw new \RuntimeException('FDShop component container is not available.');
        }

        return $this->container;
    }
}