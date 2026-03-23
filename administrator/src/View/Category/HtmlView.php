<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Category;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use stdClass;

class HtmlView extends BaseHtmlView
{
    protected $form;
    protected $item;

    public function display($tpl = null)
    {
        $item = $this->get('Item');

        if (!is_object($item)) {
            $item = new stdClass();
        }

        $form = Form::getInstance(
            'com_fdshop.category',
            JPATH_COMPONENT_ADMINISTRATOR . '/forms/category.xml',
            ['control' => 'jform']
        );

        $form->bind(get_object_vars($item));

        $this->item = $item;
        $this->form = $form;

        ToolbarHelper::title('FDShop - Kategorie');
        ToolbarHelper::apply('category.apply');
        ToolbarHelper::save('category.save');
        ToolbarHelper::cancel('category.cancel');

        parent::display($tpl);
    }
}