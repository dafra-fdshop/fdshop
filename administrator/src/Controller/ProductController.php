<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;
use FDShop\Component\FDShop\Administrator\Service\WatchlistServiceInterface;

class ProductController extends FormController
{
    protected $view_list = 'products';

    public function setPrimaryImage(): void
    {
        $this->runImageAction('primary', 'Das Hauptbild wurde geändert.');
    }

    public function updateImageOrdering(): void
    {
        $this->runImageAction('ordering', 'Die Bildreihenfolge wurde gespeichert.');
    }

    public function deleteImage(): void
    {
        $this->runImageAction('delete', 'Das Produktbild wurde gelöscht.');
    }

    public function notifyWatchlist(): void
    {
        if (!Session::checkToken()) throw new \RuntimeException('Ungültiges Sicherheitstoken.', 403);
        $user = Factory::getApplication()->getIdentity();
        if (!$user->authorise('core.edit', 'com_fdshop')) throw new \RuntimeException('Nicht berechtigt.', 403);
        $productId = $this->input->post->getInt('id');
        try {
            $result = Factory::getApplication()->bootComponent('com_fdshop')->getContainer()->get(WatchlistServiceInterface::class)->notifyAvailable($productId);
            $message = $result['success'] . ' Benachrichtigung(en) versendet, ' . $result['failed'] . ' fehlgeschlagen.';
            $type = $result['failed'] > 0 ? 'warning' : 'message';
        } catch (\Throwable $e) { $message = $e->getMessage(); $type = 'error'; }
        $this->setRedirect('index.php?option=com_fdshop&view=product&layout=edit&id=' . $productId, $message, $type);
    }

    private function runImageAction(string $action, string $successMessage): void
    {
        if (!Session::checkToken()) {
            throw new \RuntimeException('Ungültiges Sicherheitstoken.', 403);
        }
        $input = $this->input;
        $productId = $input->post->getInt('id');
        $mediaId = $input->post->getInt('media_id');
        $ordering = $input->post->getInt('media_ordering');
        $model = $this->getModel('Product');
        $ok = $model->runImageAction($action, $productId, $mediaId, $ordering);
        $this->setRedirect(
            'index.php?option=com_fdshop&view=product&layout=edit&id=' . $productId,
            $ok ? $successMessage : $model->getError(),
            $ok ? 'message' : 'error'
        );
    }
}
