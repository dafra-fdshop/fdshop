<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use FDShop\Component\FDShop\Administrator\Service\BundleServiceInterface;

class BundleModel extends AdminModel
{
    protected $text_prefix = 'COM_FDSHOP_BUNDLE';

    public function getTable($name = 'Bundle', $prefix = 'Table', $options = [])
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true): Form|false
    {
        $form = $this->loadForm(
            'com_fdshop.bundle',
            'bundle',
            [
                'control'   => 'jform',
                'load_data' => $loadData,
            ]
        );

        if (!$form) {
            return false;
        }

        return $form;
    }

    protected function loadFormData()
    {
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_fdshop.edit.bundle.data', []);

        if (!empty($data)) {
            return $data;
        }

        $item = $this->getItem();

        return $item;
    }

    public function getItem($pk = null)
    {
        $bundleId = $pk !== null
            ? (int) $pk
            : (int) Factory::getApplication()->input->getInt('id');

        if ($bundleId <= 0) {
            return parent::getItem($pk);
        }

        $item = $this->getBundleService()->getBundleById($bundleId);

        return $item ?: parent::getItem($pk);
    }

    public function getBundleProducts($pk = null): array
    {
        $bundleId = $this->resolveBundleId($pk);

        if ($bundleId <= 0) {
            return [];
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            $db->quoteName('bi.id'),
            $db->quoteName('bi.bundle_id'),
            $db->quoteName('bi.product_id'),
            $db->quoteName('bi.ordering'),
            $db->quoteName('p.product_name'),
            $db->quoteName('p.alias'),
            $db->quoteName('p.is_active'),
            $db->quoteName('p.in_stock'),
            $db->quoteName('d.sku'),
            $db->quoteName('d.gtin'),
        ])
            ->from($db->quoteName('#__fdshop_bundle_items', 'bi'))
            ->join(
                'INNER',
                $db->quoteName('#__fdshop_products', 'p')
                . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('bi.product_id')
            )
            ->join(
                'LEFT',
                $db->quoteName('#__fdshop_products_details', 'd')
                . ' ON ' . $db->quoteName('d.product_id') . ' = ' . $db->quoteName('p.id')
            )
            ->where($db->quoteName('bi.bundle_id') . ' = ' . (int) $bundleId)
            ->order($db->quoteName('bi.ordering') . ' ASC')
            ->order($db->quoteName('bi.id') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    public function getDiscountRules($pk = null): array
    {
        $bundleId = $this->resolveBundleId($pk);

        if ($bundleId <= 0) {
            return [];
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select([
            $db->quoteName('a.id'),
            $db->quoteName('a.bundle_id'),
            $db->quoteName('a.min_quantity'),
            $db->quoteName('a.discount_percent'),
            $db->quoteName('a.ordering'),
            $db->quoteName('a.created'),
            $db->quoteName('a.created_by'),
            $db->quoteName('a.modified'),
            $db->quoteName('a.modified_by'),
        ])
            ->from($db->quoteName('#__fdshop_bundle_discount_rules', 'a'))
            ->where($db->quoteName('a.bundle_id') . ' = ' . (int) $bundleId)
            ->order($db->quoteName('a.ordering') . ' ASC')
            ->order($db->quoteName('a.min_quantity') . ' ASC')
            ->order($db->quoteName('a.id') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    public function save($data): bool
    {
        try {
            $productIds = $this->normalizeIds($data['product_ids'] ?? []);
            $discountRules = [];

            if (!empty($data['discount_rules']) && is_array($data['discount_rules'])) {
                $discountRules = $data['discount_rules'];
            }

            $bundleId = $this->getBundleService()->saveBundle(
                $data,
                $productIds,
                $discountRules
            );

            $this->setState($this->getName() . '.id', $bundleId);

            return true;
        } catch (\Throwable $e) {
            $this->setError($e->getMessage());

            return false;
        }
    }

    private function resolveBundleId($pk = null): int
    {
        if ($pk !== null) {
            return (int) $pk;
        }

        return (int) Factory::getApplication()->input->getInt('id');
    }

    private function normalizeIds($ids): array
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter(
            $ids,
            static fn (int $id): bool => $id > 0
        );

        return array_values(array_unique($ids));
    }

    private function getBundleService(): BundleServiceInterface
    {
        $component = Factory::getApplication()->bootComponent('com_fdshop');
        $container = $component->getContainer();

        return $container->get(BundleServiceInterface::class);
    }
}