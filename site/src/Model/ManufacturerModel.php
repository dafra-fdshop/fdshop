<?php
namespace FDShop\Component\FDShop\Site\Model;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;
final class ManufacturerModel extends BaseDatabaseModel
{
    public function getItem(): ?object
    {
        $id = max(0, Factory::getApplication()->getInput()->getInt('id'));
        if ($id < 1) return null;
        $db = $this->getDatabase();
        $query = $db->getQuery(true)->select([$db->quoteName('id'), $db->quoteName('manufacturer_name'), $db->quoteName('alias')])->from($db->quoteName('#__fdshop_manufacturers'))->where($db->quoteName('id') . ' = :id')->where($db->quoteName('is_active') . ' = 1')->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query);
        return $db->loadObject() ?: null;
    }
}
