<?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
$input=Factory::getApplication()->getInput();
if($input->getCmd('option')!=='com_fdshop'||$input->getCmd('view')!=='category'||$input->getInt('id')<1){return;}
require __DIR__.'/tmpl/default.php';
