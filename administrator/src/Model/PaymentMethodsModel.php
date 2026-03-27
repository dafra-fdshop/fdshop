<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class PaymentmethodsModel extends ListModel
{
    protected function getListQuery()
    {
        $db = $this->getDatabase();

        return $db->getQuery(true)
            ->select([
                $db->quoteName('id'),
                $db->quoteName('payment_name'),
                $db->quoteName('payment_description'),
                $db->quoteName('payment_fee'),
                $db->quoteName('paypal_enabled'),
                $db->quoteName('is_published'),
            ])
            ->from($db->quoteName('#__fdshop_payment_methods'))
            ->order($db->quoteName('ordering') . ' ASC, ' . $db->quoteName('id') . ' ASC');
    }
}