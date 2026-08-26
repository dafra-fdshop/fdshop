<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class CouponTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__fdshop_coupons', 'id', $db);
    }

    public function getColumnAlias($column)
    {
        if ($column === 'published') {
            return 'published';
        }

        return parent::getColumnAlias($column);
    }

    public function check()
    {
        $this->coupon_code = trim((string) ($this->coupon_code ?? ''));
        $this->coupon_name = trim((string) ($this->coupon_name ?? ''));
        $this->alias = trim((string) ($this->alias ?? ''));
        $this->discount_type = trim((string) ($this->discount_type ?? 'percent'));

        if ($this->coupon_code === '') {
            $this->setError('coupon_code darf nicht leer sein.');
            return false;
        }

        if ($this->coupon_name === '') {
            $this->setError('coupon_name darf nicht leer sein.');
            return false;
        }

        if ($this->alias === '') {
            $this->alias = OutputFilter::stringURLSafe($this->coupon_name);
        } else {
            $this->alias = OutputFilter::stringURLSafe($this->alias);
        }

        if ($this->alias === '') {
            $this->alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }

        $numericFields = [
            'discount_value',
            'minimum_order_total',
            'usage_limit_total',
            'usage_limit_per_user',
            'published',
            'ordering',
        ];

        foreach ($numericFields as $field) {
            $value = $this->$field ?? null;

            if ($value === '' || $value === null) {
                $this->$field = 0;
            }
        }

        if (!isset($this->valid_from) || $this->valid_from === '') {
            $this->valid_from = null;
        }

        if (!isset($this->valid_to) || $this->valid_to === '') {
            $this->valid_to = null;
        }

        $this->published = (int) $this->published === 1 ? 1 : 0;
        $this->usage_limit_total = max(0, (int) $this->usage_limit_total);
        $this->usage_limit_per_user = max(0, (int) $this->usage_limit_per_user);

        if (empty($this->created)) {
            $this->created = Factory::getDate()->toSql();
        }

        if (empty($this->created_by)) {
            $this->created_by = (int) Factory::getApplication()->getIdentity()->id;
        }

        return true;
    }

    public function store($updateNulls = true)
    {
        $date = Factory::getDate()->toSql();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        if ((int) $this->id > 0) {
            $this->modified = $date;
            $this->modified_by = $userId;
        } else {
            if (empty($this->created)) {
                $this->created = $date;
            }

            if (empty($this->created_by)) {
                $this->created_by = $userId;
            }
        }

        return parent::store($updateNulls);
    }
}
