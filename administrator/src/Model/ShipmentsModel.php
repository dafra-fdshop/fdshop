<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class ShipmentsModel extends ListModel
{
    protected function getListQuery()
    {
        $db = $this->getDatabase();

        return $db->getQuery(true)
            ->select([
                $db->quoteName('id'),
                $db->quoteName('shipment_name'),
                $db->quoteName('shipment_description'),
                $db->quoteName('shipment_color'),
                $db->quoteName('shipment_price'),
                $db->quoteName('is_published'),
            ])
            ->from($db->quoteName('#__fdshop_shipments'))
            ->order($db->quoteName('ordering') . ' ASC, ' . $db->quoteName('id') . ' ASC');
    }
}