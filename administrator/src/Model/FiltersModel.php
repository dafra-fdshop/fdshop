<?php
namespace FDShop\Component\FDShop\Administrator\Model;
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\ListModel;
final class FiltersModel extends ListModel
{
    protected function getListQuery()
    {
        $db=$this->getDatabase();
        return $db->getQuery(true)->select(['a.*','COUNT(r.id) AS range_count'])->from($db->quoteName('#__fdshop_filters','a'))->leftJoin($db->quoteName('#__fdshop_filter_ranges','r').' ON r.filter_id=a.id')->group('a.id')->order('a.ordering ASC,a.id ASC');
    }
}
