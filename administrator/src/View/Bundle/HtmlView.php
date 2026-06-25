<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\View\Bundle;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use stdClass;

class HtmlView extends BaseHtmlView
{
    protected $form;

    protected $item;

    protected $bundleProducts = [];

    protected $discountRules = [];

    public function display($tpl = null)
    {
        $model = $this->getModel();

        $this->item = $model->getItem();
        $this->form = $model->getForm();

        if (!is_object($this->item)) {
            $this->item = new stdClass();
        }

        $this->bundleProducts = $model->getBundleProducts();
        $this->discountRules  = $model->getDiscountRules();

        if (empty($this->discountRules)) {
            $rule = new stdClass();
            $rule->min_quantity = 1;
            $rule->discount_percent = 0;
            $rule->ordering = 1;

            $this->discountRules = [$rule];
        }

        $title = 'FDShop - Bundle';

        if (!empty($this->item->bundle_number)) {
            $title .= ' ' . $this->item->bundle_number;
        }

        ToolbarHelper::title($title);
        ToolbarHelper::apply('bundle.apply');
        ToolbarHelper::save('bundle.save');
        ToolbarHelper::cancel('bundle.cancel');

        parent::display($tpl);
    }
}
