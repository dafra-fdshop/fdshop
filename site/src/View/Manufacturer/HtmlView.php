<?php
namespace FDShop\Component\FDShop\Site\View\Manufacturer;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
final class HtmlView extends BaseHtmlView
{
    public object $item;
    public function display($tpl = null): void
    {
        $item = $this->getModel()->getItem();
        if ($item === null) throw new \RuntimeException('Der gewählte FDShop-Hersteller wurde nicht gefunden.', 404);
        $this->item = $item;
        $document = Factory::getApplication()->getDocument();
        $document->setTitle((string) $item->manufacturer_name);
        $document->getWebAssetManager()->useStyle('com_fdshop.site');
        parent::display($tpl);
    }
}
