<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Dashboard;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Factory;
use FDShop\Component\FDShop\Administrator\Service\WatchlistServiceInterface;

class HtmlView extends BaseHtmlView
{
    protected array $watchlist = [];
    public function display($tpl = null)
    {
        $this->watchlist = Factory::getApplication()->bootComponent('com_fdshop')->getContainer()->get(WatchlistServiceInterface::class)->dashboard();
        parent::display($tpl);
    }
}
