<?php
namespace FDShop\Component\FDShop\Administrator\Model;
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
final class FiltersModel extends ListModel
{
    protected function getListQuery()
    {
        $db=$this->getDatabase();
        return $db->getQuery(true)
            ->select([
                'a.*',
                'COUNT(DISTINCT r.id) AS range_count',
                'COUNT(DISTINCT o.id) AS option_count',
            ])
            ->from($db->quoteName('#__fdshop_filters', 'a'))
            ->leftJoin($db->quoteName('#__fdshop_filter_ranges', 'r') . ' ON r.filter_id=a.id')
            ->leftJoin($db->quoteName('#__fdshop_filter_options', 'o') . ' ON o.filter_id=a.id')
            ->group('a.id')
            ->order('a.ordering ASC,a.id ASC');
    }
}
