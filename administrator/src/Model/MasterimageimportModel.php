<?php
namespace FDShop\Component\FDShop\Administrator\Model;
defined('_JEXEC') or die;
use FDShop\Component\FDShop\Administrator\Service\MasterImageImportService;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
final class MasterimageimportModel extends BaseDatabaseModel
{
    public function getPageData(): array {$service=Factory::getApplication()->bootComponent('com_fdshop')->getContainer()->get(MasterImageImportService::class);$paths=$service->getPaths();$analysis=$service->analyse();return ['paths'=>$paths,'analysis'=>$analysis,'zip'=>class_exists(\ZipArchive::class),'limits'=>['upload_max_filesize'=>ini_get('upload_max_filesize'),'post_max_size'=>ini_get('post_max_size'),'max_file_uploads'=>ini_get('max_file_uploads')]];}
}
