<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;

class CategoriesModel extends ListModel
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
            $db->quoteName('a.parent_id'),
            $db->quoteName('a.category_name'),
            $db->quoteName('a.alias'),
            $db->quoteName('a.path'),
            $db->quoteName('a.description'),
            $db->quoteName('a.level'),
            $db->quoteName('a.ordering'),
            $db->quoteName('a.is_active'),
            $db->quoteName('a.created'),
            $db->quoteName('a.created_by'),
            $db->quoteName('a.modified'),
            $db->quoteName('a.modified_by'),
        ])
            ->from($db->quoteName('#__fdshop_categories', 'a'))
            ->order(
                $db->quoteName($this->state->get('list.ordering', 'a.ordering')) . ' ' .
                $db->escape($this->state->get('list.direction', 'ASC'))
            );

        return $query;
    }
}