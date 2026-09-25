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

    public function save(): bool
    {
        if (!$this->authoriseMutation()) { return false; }
        $orderId=$this->input->post->getInt('id');
        try {
            $changed=$this->getOrderService()->saveDraft(
                $orderId,
                (array)$this->input->post->get('items', [], 'array'),
                (array)$this->input->post->get('new_items', [], 'array'),
                $this->input->post->getInt('shipment_id'),
                $this->input->post->getString('expected_modified')
            );
            $this->setMessage($changed?'Bestellung wurde atomar gespeichert.':'Es lagen keine Änderungen vor.');
            $this->setRedirect($this->getOrderRedirect($orderId));
        } catch (\Throwable $e) {$this->setMessage($e->getMessage(),'error');$this->setRedirect($this->getOrderRedirect($orderId));}
        return true;
    }

    public function cancel(): bool
    {
        if (!$this->authoriseMutation()) { return false; }
        $this->setRedirect('index.php?option=com_fdshop&view=orders');
        return true;
    }

    public function addItem(): bool
    {
        if (!$this->authoriseMutation()) { return false; }
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
        if (!$this->authoriseMutation()) { return false; }
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
        if (!$this->authoriseMutation()) { return false; }
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

    private function authoriseMutation(): bool
    {
        if (strtoupper($this->input->getMethod()) !== 'POST') {
            $this->setMessage('Bestelländerungen sind ausschließlich per POST zulässig.', 'error');
            $this->setRedirect('index.php?option=com_fdshop&view=orders');

            return false;
        }
        $this->checkToken();
        if (!Factory::getApplication()->getIdentity()->authorise('core.manage', 'com_fdshop')) {
            $this->setMessage('Sie sind nicht berechtigt, Bestellungen zu bearbeiten.', 'error');
            $this->setRedirect('index.php?option=com_fdshop&view=orders');
            return false;
        }
        return true;
    }
}
