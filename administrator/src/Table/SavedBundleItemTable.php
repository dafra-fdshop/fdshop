<?php
namespace FDShop\Component\FDShop\Administrator\Table;
defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
final class SavedBundleItemTable extends Table { public function __construct(DatabaseDriver $db) { parent::__construct('#__fdshop_saved_bundle_items', 'id', $db); } }
