<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\Database\DatabaseInterface;

class ProductController extends FormController
{
    protected $view_list = 'products';

    public function save($key = null, $urlVar = null)
    {
        $data = $this->input->post->get('jform', [], 'array');
        $model = $this->getModel();
        $task = $this->getTask();

        if (!$model->save($data)) {
            $this->setMessage($model->getError(), 'error');

            $id = (int) ($data['id'] ?? 0);
            $redirect = 'index.php?option=com_fdshop&view=product&layout=edit';

            if ($id > 0) {
                $redirect .= '&id=' . $id;
            }

            $this->setRedirect($redirect);

            return false;
        }

        $id = (int) $model->getState($model->getName() . '.id');

        $this->saveCategoryAssignments(
            $id,
            $this->normalizeCategoryIds($data['category_ids'] ?? [])
        );

        $this->setMessage('Produkt gespeichert.');

        if ($task === 'apply') {
            $this->setRedirect(
                'index.php?option=com_fdshop&view=product&layout=edit&id=' . $id
            );

            return true;
        }

        $this->setRedirect('index.php?option=com_fdshop&view=products');

        return true;
    }

    private function normalizeCategoryIds($categoryIds): array
    {
        if (!is_array($categoryIds)) {
            $categoryIds = [$categoryIds];
        }

        $categoryIds = array_map('intval', $categoryIds);
        $categoryIds = array_filter(
            $categoryIds,
            static fn (int $categoryId): bool => $categoryId > 0
        );

        return array_values(array_unique($categoryIds));
    }

    private function saveCategoryAssignments(int $productId, array $categoryIds): void
    {
        if ($productId <= 0) {
            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $deleteQuery = $db->getQuery(true)
            ->delete($db->quoteName('#__fdshop_product_category_map'))
            ->where($db->quoteName('product_id') . ' = ' . (int) $productId);

        $db->setQuery($deleteQuery)->execute();

        foreach ($categoryIds as $index => $categoryId) {
            $isPrimary = ($index === 0) ? 1 : 0;

            $insertQuery = $db->getQuery(true)
                ->insert($db->quoteName('#__fdshop_product_category_map'))
                ->columns([
                    $db->quoteName('product_id'),
                    $db->quoteName('category_id'),
                    $db->quoteName('is_primary'),
                ])
                ->values(
                    implode(', ', [
                        (int) $productId,
                        (int) $categoryId,
                        (int) $isPrimary,
                    ])
                );

            $db->setQuery($insertQuery)->execute();
        }
    }
}