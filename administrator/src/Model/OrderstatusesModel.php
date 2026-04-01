<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class OrderstatusesModel extends ListModel
{
    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.status_code'),
            $db->quoteName('a.status_name'),
            $db->quoteName('a.seller_email_mode'),
            $db->quoteName('a.seller_email_address'),
            $db->quoteName('a.buyer_email_mode'),
            $db->quoteName('a.create_invoice'),
            $db->quoteName('a.stock_action'),
        ])
            ->from($db->quoteName('#__fdshop_order_statuses', 'a'))
            ->order($db->quoteName('a.ordering') . ' ASC');

        return $query;
    }
}