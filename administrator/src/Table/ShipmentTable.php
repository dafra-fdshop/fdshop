<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

class ShipmentTable extends Table
{
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__fdshop_shipments', 'id', $db);
    }

    public function check()
    {
        $numericFields = [
            'shipment_price',
            'published',
            'is_default',
            'ordering',
        ];

        foreach ($numericFields as $field) {
            $value = $this->$field ?? null;

            if ($value === '' || $value === null) {
                $this->$field = 0;
            }
        }

        $this->shipment_name = trim((string) ($this->shipment_name ?? ''));
        $this->shipment_color = trim((string) ($this->shipment_color ?? ''));
        $this->shipment_description = (string) ($this->shipment_description ?? '');

        if ($this->shipment_name === '') {
            $this->setError('shipment_name darf nicht leer sein.');
            return false;
        }

        $this->published = (int) $this->published === 1 ? 1 : 0;
        $this->is_default = (int) $this->is_default === 1 ? 1 : 0;

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
