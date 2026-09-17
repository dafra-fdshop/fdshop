<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;

$app = Factory::getApplication();
$input = $app->getInput();

if ($input->getCmd('option') !== 'com_fdshop' || $input->getCmd('view') !== 'category' || $input->getInt('id') < 1) {
    return;
}

$model = $app->bootComponent('com_fdshop')->getMVCFactory()->createModel('Category', 'Site', ['ignore_request' => false]);

if (!$model || !method_exists($model, 'getFilterRenderData')) {
    return;
}

$filterData = $model->getFilterRenderData();

if ($filterData === null) {
    return;
}

require ModuleHelper::getLayoutPath('mod_fdshop_filter', $params->get('layout', 'default'));
