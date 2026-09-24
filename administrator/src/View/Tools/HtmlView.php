<?php
namespace FDShop\Component\FDShop\Administrator\View\Tools;
defined('_JEXEC') or die;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
final class HtmlView extends BaseHtmlView {public function display($tpl=null){if(!$this->getCurrentUser()->authorise('core.manage','com_fdshop'))throw new \RuntimeException('Keine Berechtigung.',403);ToolbarHelper::title('FDShop Tools');parent::display($tpl);}}
