<?php
namespace FDShop\Component\FDShop\Administrator\Table;
defined('_JEXEC') or die;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
final class CartBundleTable extends Table { public function __construct(DatabaseDriver $db) { parent::__construct('#__fdshop_cart_bundles', 'id', $db); } }
