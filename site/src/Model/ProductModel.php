<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\ParameterType;

final class ProductModel extends BaseDatabaseModel
{
    public function getItem(): ?object
    {
        $productId = max(0, Factory::getApplication()->getInput()->getInt('id'));

        if ($productId < 1) {
            return null;
        }

        $db = $this->getDatabase();
        $now = Factory::getDate()->toSql();
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('id'),
                $db->quoteName('product_name'),
                $db->quoteName('short_description'),
            ])
            ->from($db->quoteName('#__fdshop_products'))
            ->where($db->quoteName('id') . ' = :productId')
            ->where($db->quoteName('is_active') . ' = 1')
            ->where($db->quoteName('is_deleted') . ' = 0')
            ->where('(' . $db->quoteName('publish_up') . ' IS NULL OR ' . $db->quoteName('publish_up') . ' <= :publishUp)')
            ->where('(' . $db->quoteName('publish_down') . ' IS NULL OR ' . $db->quoteName('publish_down') . ' >= :publishDown)')
            ->bind(':productId', $productId, ParameterType::INTEGER)
            ->bind(':publishUp', $now)
            ->bind(':publishDown', $now);

        $db->setQuery($query);
        $item = $db->loadObject();

        return $item ?: null;
    }
}
