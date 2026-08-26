<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Coupon;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use stdClass;

class HtmlView extends BaseHtmlView
{
    protected $form;

    protected $item;

    public function display($tpl = null)
    {
        $model = $this->getModel();

        $this->item = $model->getItem();
        $this->form = $model->getForm();

        if (!is_object($this->item)) {
            $this->item = new stdClass();
        }

        $title = 'FDShop - Gutschein';

        if (!empty($this->item->coupon_code)) {
            $title .= ' ' . $this->item->coupon_code;
        }

        ToolbarHelper::title($title);
        ToolbarHelper::apply('coupon.apply');
        ToolbarHelper::save('coupon.save');
        ToolbarHelper::cancel('coupon.cancel');

        parent::display($tpl);
    }
}
