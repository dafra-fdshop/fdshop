<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Controller;

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Administrator\Service\OrderServiceInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

class OrderController extends BaseController
{
    protected $default_view = 'order';

    public function addItem(): bool
    {
        $orderId   = $this->input->getInt('id');
        $productId = $this->input->getInt('product_id');
        $quantity  = (float) $this->input->get('quantity', 1, 'float');

        try {
            $this->getOrderService()->addItem($orderId, $productId, $quantity);
            $this->setMessage('Bestellposition hinzugefügt.');
        } catch (\Throwable $e) {
            $this->setMessage($e->getMessage(), 'error');
        }

        $this->setRedirect($this->getOrderRedirect($orderId));

        return true;
    }

    public function removeItem(): bool
    {
        $orderId     = $this->input->getInt('id');
        $orderItemId = $this->input->getInt('order_item_id');

        try {
            $this->getOrderService()->removeItem($orderId, $orderItemId);
            $this->setMessage('Bestellposition entfernt.');
        } catch (\Throwable $e) {
            $this->setMessage($e->getMessage(), 'error');
        }

        $this->setRedirect($this->getOrderRedirect($orderId));

        return true;
    }

    public function updateItemQuantity(): bool
    {
        $orderId     = $this->input->getInt('id');
        $orderItemId = $this->input->getInt('order_item_id');
        $quantity    = (float) $this->input->get('quantity', 0, 'float');

        try {
            $this->getOrderService()->changeItemQuantity($orderId, $orderItemId, $quantity);
            $this->setMessage('Menge aktualisiert.');
        } catch (\Throwable $e) {
            $this->setMessage($e->getMessage(), 'error');
        }

        $this->setRedirect($this->getOrderRedirect($orderId));

        return true;
    }

    private function getOrderService(): OrderServiceInterface
    {
        $component = Factory::getApplication()->bootComponent('com_fdshop');
        $container = $component->getContainer();

        return $container->get(OrderServiceInterface::class);
    }

    private function getOrderRedirect(int $orderId): string
    {
        return 'index.php?option=com_fdshop&view=order&id=' . $orderId;
    }
}