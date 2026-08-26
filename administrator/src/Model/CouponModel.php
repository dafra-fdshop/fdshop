<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use FDShop\Component\FDShop\Administrator\Service\CouponServiceInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;

class CouponModel extends AdminModel
{
    protected $text_prefix = 'COM_FDSHOP_COUPON';

    public function getTable($name = 'Coupon', $prefix = 'Table', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true): Form|false
    {
        $form = $this->loadForm(
            'com_fdshop.coupon',
            'coupon',
            [
                'control'   => 'jform',
                'load_data' => $loadData,
            ]
        );

        if (!$form) {
            return false;
        }

        return $form;
    }

    protected function loadFormData()
    {
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_fdshop.edit.coupon.data', []);

        if (!empty($data)) {
            return $data;
        }

        return $this->getItem();
    }

    public function getItem($pk = null)
    {
        $couponId = $this->resolveCouponId($pk);

        if ($couponId <= 0) {
            return parent::getItem($pk);
        }

        $item = $this->getCouponService()->getCouponById($couponId);

        return $item ?: parent::getItem($pk);
    }

    public function getCouponMappings($pk = null): array
    {
        $couponId = $this->resolveCouponId($pk);

        if ($couponId <= 0) {
            return [
                'user_ids'        => [],
                'buyer_group_ids' => [],
                'product_ids'     => [],
                'category_ids'    => [],
            ];
        }

        return $this->getCouponService()->getCouponMappings($couponId);
    }

    public function save($data): bool
    {
        try {
            $couponId = $this->getCouponService()->saveCoupon((array) $data);

            $this->setState($this->getName() . '.id', $couponId);

            return true;
        } catch (\Throwable $e) {
            $this->setError($e->getMessage());

            return false;
        }
    }

    public function delete(&$pks): bool
    {
        try {
            return $this->getCouponService()->deleteCoupons((array) $pks);
        } catch (\Throwable $e) {
            $this->setError($e->getMessage());

            return false;
        }
    }

    private function resolveCouponId($pk = null): int
    {
        if ($pk !== null) {
            return (int) $pk;
        }

        return (int) Factory::getApplication()->input->getInt('id');
    }

    private function getCouponService(): CouponServiceInterface
    {
        $component = Factory::getApplication()->bootComponent('com_fdshop');
        $container = $component->getContainer();

        return $container->get(CouponServiceInterface::class);
    }
}
