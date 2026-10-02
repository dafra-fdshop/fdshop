<?php

namespace FDShop\Component\FDShop\Site\Model;

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Site\Service\CartServiceInterface;
use FDShop\Component\FDShop\Site\Service\CartContinuationServiceInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

final class CartModel extends BaseDatabaseModel
{
    public function getCartData(): ?array
    {
        $app = Factory::getApplication();
        $userId = (int) $app->getIdentity()->id;
        $session = $app->getSession();

        return $this->getCartService()->getCart(
            $userId,
            $session->getId(),
            (int) $session->get('com_fdshop.cart.' . $userId . '.shipment_id', 0),
            (int) $session->get('com_fdshop.cart.' . $userId . '.payment_id', 0),
            (string) $session->get('com_fdshop.cart.' . $userId . '.coupon_code', '')
        );
    }

    public function getConfig(): object
    {
        $query = $this->getDatabase()->getQuery(true)
            ->select([$this->getDatabase()->quoteName('show_terms_checkbox'), $this->getDatabase()->quoteName('require_terms_checkbox')])
            ->from($this->getDatabase()->quoteName('#__fdshop_config'))
            ->where($this->getDatabase()->quoteName('id') . ' = 1');
        $this->getDatabase()->setQuery($query);
        return $this->getDatabase()->loadObject() ?: (object) ['show_terms_checkbox' => 0, 'require_terms_checkbox' => 0];
    }

    public function getConflict(): ?array
    {
        $app = Factory::getApplication();
        $userId = (int) $app->getIdentity()->id;
        $token = $app->getInput()->cookie->getString(CartContinuationServiceInterface::COOKIE_NAME, '');
        if ($userId < 1 || $token === '') return null;
        $session = $app->getSession();
        return $this->bootComponent('com_fdshop')->getContainer()->get(CartContinuationServiceInterface::class)->getConflict($token, $userId, [
            'shipment_id' => (int) $session->get('com_fdshop.cart.' . $userId . '.shipment_id', 0),
            'payment_id' => (int) $session->get('com_fdshop.cart.' . $userId . '.payment_id', 0),
            'coupon_code' => (string) $session->get('com_fdshop.cart.' . $userId . '.coupon_code', ''),
        ]);
    }

    private function getCartService(): CartServiceInterface
    {
        return $this->bootComponent('com_fdshop')->getContainer()->get(CartServiceInterface::class);
    }
}
