<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class ProductsModel extends ListModel
{
    protected function populateState($ordering = 'a.ordering', $direction = 'ASC'): void
    {
        parent::populateState($ordering, $direction);
    }

    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.sku'),
            $db->quoteName('a.gtin'),
            $db->quoteName('a.manufacturer_id'),
            $db->quoteName('a.product_name'),
            $db->quoteName('a.alias'),
            $db->quoteName('a.short_description'),
            $db->quoteName('a.description'),
            $db->quoteName('a.active_price_net'),
            $db->quoteName('a.active_price_gross'),
            $db->quoteName('a.active_tax_rate'),
            $db->quoteName('a.currency'),
            $db->quoteName('a.stock_quantity'),
            $db->quoteName('a.reserved_quantity'),
            $db->quoteName('a.min_order_qty'),
            $db->quoteName('a.max_order_qty'),
            $db->quoteName('a.step_order_qty'),
            $db->quoteName('a.is_active'),
            $db->quoteName('a.is_featured'),
            $db->quoteName('a.access'),
            $db->quoteName('a.ordering'),
            $db->quoteName('a.publish_up'),
            $db->quoteName('a.publish_down'),
            $db->quoteName('a.created'),
            $db->quoteName('a.created_by'),
            $db->quoteName('a.modified'),
            $db->quoteName('a.modified_by'),
        ])
            ->from($db->quoteName('#__fdshop_products', 'a'))
            ->order(
                $db->quoteName($this->state->get('list.ordering', 'a.ordering')) . ' ' .
                $db->escape($this->state->get('list.direction', 'ASC'))
            );

        return $query;
    }
}