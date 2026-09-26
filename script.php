<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\Filesystem\Folder;

final class Com_FdshopInstallerScript implements InstallerScriptInterface
{
	/**
	 * Mindestversionen optional bewusst nicht gesetzt,
	 * da Auftrag nur Ordnerstruktur bei Install/Update sicherstellen soll.
	 */

	public function install(InstallerAdapter $adapter): bool
	{
		return $this->ensureImageFolders();
	}

	public function update(InstallerAdapter $adapter): bool
	{
		return $this->ensureImageFolders();
	}

	public function uninstall(InstallerAdapter $adapter): bool
	{
		return true;
	}

	public function preflight(string $type, InstallerAdapter $adapter): bool
	{
		return true;
	}

	public function postflight(string $type, InstallerAdapter $adapter): bool
	{
		// Zusätzliche Absicherung, falls Joomla-Ablauf je nach Typ variiert.
		if ($type === 'install' || $type === 'update')
		{
			return $this->ensureImageFolders();
		}

		return true;
	}

	private function ensureImageFolders(): bool
	{
		$paths = [
			JPATH_ROOT . '/images/FDShop',
			JPATH_ROOT . '/images/FDShop/manufacturer',
			JPATH_ROOT . '/images/FDShop/products',
			JPATH_ROOT . '/images/FDShop/products/standard',
			JPATH_ROOT . '/images/FDShop/products/small',
			JPATH_ROOT . '/images/FDShop/products/mobile',
			JPATH_ROOT . '/images/FDShop/products/invoices',
		];

		foreach ($paths as $path)
		{
			if (!is_dir($path))
			{
				if (!Folder::create($path))
				{
					Factory::getApplication()->enqueueMessage(
						'FDShop: Ordner konnte nicht angelegt werden: ' . $path,
						'error'
					);

					return false;
				}
			}
		}

		$documents = JPATH_ADMINISTRATOR . '/components/com_fdshop/documents';
		if (!is_dir($documents) && !Folder::create($documents))
		{
			Factory::getApplication()->enqueueMessage('FDShop: Geschützter Dokumentordner konnte nicht angelegt werden.', 'error');
			return false;
		}
		if (!is_file($documents . '/.htaccess')) file_put_contents($documents . '/.htaccess', "Require all denied\nDeny from all\n");
		if (!is_file($documents . '/index.html')) file_put_contents($documents . '/index.html', '');

		return true;
	}
}
