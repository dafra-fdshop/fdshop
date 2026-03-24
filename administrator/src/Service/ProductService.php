<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

namespace FDShop\Component\FDShop\Administrator\Service;

defined('_JEXEC') or die;

use InvalidArgumentException;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use RuntimeException;

class ProductService implements ProductServiceInterface
{
    public function __construct(
        private readonly MVCFactoryInterface $mvcFactory,
        private readonly DatabaseInterface $db
    ) {
    }

    public function saveProduct(
        array $data,
        array $categoryIds = [],
        ?int $primaryCategoryId = null,
        array $buyerGroupIds = []
    ): int {
        $productName = trim((string) ($data['product_name'] ?? ''));

        if ($productName === '') {
            throw new InvalidArgumentException('product_name darf nicht leer sein.');
        }

        if ($categoryIds === [] && array_key_exists('category_ids', $data)) {
            $categoryIds = $this->normalizeIds($data['category_ids']);
        } else {
            $categoryIds = $this->normalizeIds($categoryIds);
        }

        if ($buyerGroupIds === [] && array_key_exists('buyer_group_ids', $data)) {
            $buyerGroupIds = $this->normalizeIds($data['buyer_group_ids']);
        } else {
            $buyerGroupIds = $this->normalizeIds($buyerGroupIds);
        }

        if ($primaryCategoryId === null && !empty($data['primary_category_id'])) {
            $primaryCategoryId = (int) $data['primary_category_id'];
        }

        $table = $this->mvcFactory->createTable('Product', 'Administrator');

        if (!$table) {
            throw new RuntimeException('ProductTable konnte nicht erstellt werden.');
        }

        $bindData = $data;
        $bindData['product_name'] = $productName;

        unset($bindData['category_ids'], $bindData['buyer_group_ids'], $bindData['primary_category_id']);

        if (!$table->bind($bindData)) {
            throw new RuntimeException($table->getError());
        }

        if (!$table->check()) {
            throw new RuntimeException($table->getError());
        }

        if (!$table->store()) {
            throw new RuntimeException($table->getError());
        }

        $productId = (int) $table->id;

        $this->saveProductCategoryAssignments($productId, $categoryIds, $primaryCategoryId);
        $this->saveProductBuyerGroupAssignments($productId, $buyerGroupIds);
        $this->processUploadedProductImage($productId);

        return $productId;
    }

    public function saveProductCategoryAssignments(
        int $productId,
        array $categoryIds,
        ?int $primaryCategoryId = null
    ): void {
        if ($productId <= 0) {
            throw new InvalidArgumentException('productId ist ungültig.');
        }

        $categoryIds = $this->normalizeIds($categoryIds);

        $deleteQuery = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__fdshop_product_category_map'))
            ->where($this->db->quoteName('product_id') . ' = ' . (int) $productId);

        $this->db->setQuery($deleteQuery)->execute();

        if ($categoryIds === []) {
            return;
        }

        if ($primaryCategoryId !== null && !in_array($primaryCategoryId, $categoryIds, true)) {
            $primaryCategoryId = null;
        }

        foreach ($categoryIds as $index => $categoryId) {
            $isPrimary = 0;

            if ($primaryCategoryId !== null) {
                $isPrimary = ($categoryId === $primaryCategoryId) ? 1 : 0;
            } elseif ($index === 0) {
                $isPrimary = 1;
            }

            $insertQuery = $this->db->getQuery(true)
                ->insert($this->db->quoteName('#__fdshop_product_category_map'))
                ->columns([
                    $this->db->quoteName('product_id'),
                    $this->db->quoteName('category_id'),
                    $this->db->quoteName('is_primary'),
                ])
                ->values(
                    implode(', ', [
                        (int) $productId,
                        (int) $categoryId,
                        (int) $isPrimary,
                    ])
                );

            $this->db->setQuery($insertQuery)->execute();
        }
    }

    public function saveProductBuyerGroupAssignments(
        int $productId,
        array $buyerGroupIds
    ): void {
        if ($productId <= 0) {
            throw new InvalidArgumentException('productId ist ungültig.');
        }

        $buyerGroupIds = $this->normalizeIds($buyerGroupIds);

        $deleteQuery = $this->db->getQuery(true)
            ->delete($this->db->quoteName('#__fdshop_product_buyer_group_map'))
            ->where($this->db->quoteName('product_id') . ' = ' . (int) $productId);

        $this->db->setQuery($deleteQuery)->execute();

        if ($buyerGroupIds === []) {
            return;
        }

        foreach ($buyerGroupIds as $buyerGroupId) {
            $insertQuery = $this->db->getQuery(true)
                ->insert($this->db->quoteName('#__fdshop_product_buyer_group_map'))
                ->columns([
                    $this->db->quoteName('product_id'),
                    $this->db->quoteName('buyer_group_id'),
                ])
                ->values(
                    implode(', ', [
                        (int) $productId,
                        (int) $buyerGroupId,
                    ])
                );

            $this->db->setQuery($insertQuery)->execute();
        }
    }

    public function getAssignedCategoryIds(int $productId): array
    {
        if ($productId <= 0) {
            return [];
        }

        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('category_id'))
            ->from($this->db->quoteName('#__fdshop_product_category_map'))
            ->where($this->db->quoteName('product_id') . ' = ' . (int) $productId)
            ->order($this->db->quoteName('is_primary') . ' DESC, ' . $this->db->quoteName('id') . ' ASC');

        $this->db->setQuery($query);

        return array_map('intval', (array) $this->db->loadColumn());
    }

    public function getAssignedBuyerGroupIds(int $productId): array
    {
        if ($productId <= 0) {
            return [];
        }

        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('buyer_group_id'))
            ->from($this->db->quoteName('#__fdshop_product_buyer_group_map'))
            ->where($this->db->quoteName('product_id') . ' = ' . (int) $productId)
            ->order($this->db->quoteName('id') . ' ASC');

        $this->db->setQuery($query);

        return array_map('intval', (array) $this->db->loadColumn());
    }

    public function getProductById(int $productId): ?object
    {
        if ($productId <= 0) {
            return null;
        }

        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName('#__fdshop_products'))
            ->where($this->db->quoteName('id') . ' = ' . (int) $productId);

        $this->db->setQuery($query);

        $item = $this->db->loadObject();

        if (!$item) {
            return null;
        }

        $item->category_ids = $this->getAssignedCategoryIds($productId);
        $item->buyer_group_ids = $this->getAssignedBuyerGroupIds($productId);

        return $item;
    }

    private function processUploadedProductImage(int $productId): void
    {
        $file = $this->getUploadedProductImageFile();

        if ($file === null) {
            return;
        }

        $this->assertGdAvailable();

        if (!is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new RuntimeException('Die hochgeladene Bilddatei ist ungültig.');
        }

        $imageInfo = @getimagesize($file['tmp_name']);

        if ($imageInfo === false || empty($imageInfo['mime'])) {
            throw new RuntimeException('Die hochgeladene Datei ist kein gültiges Bild.');
        }

        $sourceMime = (string) $imageInfo['mime'];
        $sourceExtension = $this->getExtensionForMime($sourceMime);

        $config = $this->loadImageConfiguration();
        $baseName = $this->generateMediaBaseName($productId);

        $paths = [
            'standard' => '/images/FDShop/products/standard/',
            'small'    => '/images/FDShop/products/small/',
            'mobile'   => '/images/FDShop/products/mobile/',
            'invoice'  => '/images/FDShop/products/invoices/',
        ];

        $createdFiles = [];

        try {
            $this->ensureDirectory(JPATH_ROOT . $paths['standard']);
            $this->ensureDirectory(JPATH_ROOT . $paths['small']);
            $this->ensureDirectory(JPATH_ROOT . $paths['mobile']);
            $this->ensureDirectory(JPATH_ROOT . $paths['invoice']);

            $sourceImage = $this->createImageResource($file['tmp_name'], $sourceMime);

            if (!$sourceImage) {
                throw new RuntimeException('Das Bild konnte mit GD nicht geladen werden.');
            }

            $standardFileName = $baseName . '.' . $sourceExtension;
            $smallFileName = $baseName . '.' . $sourceExtension;
            $mobileFileName = $baseName . '.' . $sourceExtension;
            $invoiceFileName = $baseName . '.png';

            $standardRelativePath = $paths['standard'] . $standardFileName;
            $smallRelativePath = $paths['small'] . $smallFileName;
            $mobileRelativePath = $paths['mobile'] . $mobileFileName;
            $invoiceRelativePath = $paths['invoice'] . $invoiceFileName;

            $this->createSquareVariant(
                $sourceImage,
                (int) $config['image_size_default'],
                $sourceMime,
                JPATH_ROOT . $standardRelativePath
            );
            $createdFiles[] = JPATH_ROOT . $standardRelativePath;

            $this->createSquareVariant(
                $sourceImage,
                (int) $config['image_size_small'],
                $sourceMime,
                JPATH_ROOT . $smallRelativePath
            );
            $createdFiles[] = JPATH_ROOT . $smallRelativePath;

            $this->createSquareVariant(
                $sourceImage,
                (int) $config['image_size_mobile'],
                $sourceMime,
                JPATH_ROOT . $mobileRelativePath
            );
            $createdFiles[] = JPATH_ROOT . $mobileRelativePath;

            $this->createSquareVariant(
                $sourceImage,
                (int) $config['image_size_mobile'],
                'image/png',
                JPATH_ROOT . $invoiceRelativePath
            );
            $createdFiles[] = JPATH_ROOT . $invoiceRelativePath;

            imagedestroy($sourceImage);

            $this->insertMediaRecord(
                $productId,
                $standardFileName,
                $sourceMime,
                $standardRelativePath,
                $smallRelativePath,
                $mobileRelativePath,
                $invoiceRelativePath
            );
        } catch (\Throwable $e) {
            foreach ($createdFiles as $createdFile) {
                if (is_file($createdFile)) {
                    @unlink($createdFile);
                }
            }

            throw $e;
        }
    }

    private function getUploadedProductImageFile(): ?array
    {
        $files = Factory::getApplication()->input->files->get('jform', [], 'array');
        $file = $files['product_image'] ?? null;

        if (!is_array($file)) {
            return null;
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE || empty($file['tmp_name'])) {
            return null;
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Der Bild-Upload ist fehlgeschlagen (Fehlercode: ' . $error . ').');
        }

        return $file;
    }

    private function assertGdAvailable(): void
    {
        if (!extension_loaded('gd') || !function_exists('gd_info')) {
            throw new RuntimeException('Die PHP-Erweiterung GD ist nicht verfügbar. Bildverarbeitung ist nicht möglich.');
        }
    }

    private function loadImageConfiguration(): array
    {
        $query = $this->db->getQuery(true)
            ->select([
                $this->db->quoteName('image_size_default'),
                $this->db->quoteName('image_size_small'),
                $this->db->quoteName('image_size_mobile'),
            ])
            ->from($this->db->quoteName('#__fdshop_config'))
            ->where($this->db->quoteName('id') . ' = 1');

        $this->db->setQuery($query);
        $config = (array) $this->db->loadAssoc();

        return [
            'image_size_default' => max(1, (int) ($config['image_size_default'] ?? 400)),
            'image_size_small'   => max(1, (int) ($config['image_size_small'] ?? 250)),
            'image_size_mobile'  => max(1, (int) ($config['image_size_mobile'] ?? 100)),
        ];
    }

    private function generateMediaBaseName(int $productId): string
    {
        $random = bin2hex(random_bytes(6));

        return 'product-' . $productId . '-' . $random;
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!Folder::create($directory)) {
            throw new RuntimeException('Das Verzeichnis konnte nicht erstellt werden: ' . $directory);
        }
    }

    private function createImageResource(string $tmpFile, string $mime)
    {
        $content = @file_get_contents($tmpFile);

        if ($content === false) {
            throw new RuntimeException('Die hochgeladene Bilddatei konnte nicht gelesen werden.');
        }

        $image = @imagecreatefromstring($content);

        if ($image === false) {
            throw new RuntimeException('Die hochgeladene Bilddatei wird nicht unterstützt.');
        }

        return $image;
    }

    private function createSquareVariant($sourceImage, int $targetSize, string $outputMime, string $targetPath): void
    {
        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            throw new RuntimeException('Ungültige Bildabmessungen.');
        }

        $cropSize = min($sourceWidth, $sourceHeight);
        $srcX = (int) floor(($sourceWidth - $cropSize) / 2);
        $srcY = (int) floor(($sourceHeight - $cropSize) / 2);

        $targetImage = imagecreatetruecolor($targetSize, $targetSize);

        if ($targetImage === false) {
            throw new RuntimeException('Die Bildvariante konnte nicht erzeugt werden.');
        }

        $this->prepareTargetCanvas($targetImage, $outputMime);

        if (!imagecopyresampled(
            $targetImage,
            $sourceImage,
            0,
            0,
            $srcX,
            $srcY,
            $targetSize,
            $targetSize,
            $cropSize,
            $cropSize
        )) {
            imagedestroy($targetImage);
            throw new RuntimeException('Die Bildskalierung ist fehlgeschlagen.');
        }

        $saved = $this->saveImageResource($targetImage, $outputMime, $targetPath);

        imagedestroy($targetImage);

        if (!$saved) {
            throw new RuntimeException('Die Bilddatei konnte nicht gespeichert werden: ' . $targetPath);
        }
    }

    private function prepareTargetCanvas($image, string $mime): void
    {
        if (in_array($mime, ['image/png', 'image/gif', 'image/webp'], true)) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $transparent);

            return;
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $white);
    }

    private function saveImageResource($image, string $mime, string $targetPath): bool
    {
        return match ($mime) {
            'image/jpeg', 'image/pjpeg' => imagejpeg($image, $targetPath, 90),
            'image/png'                 => imagepng($image, $targetPath, 6),
            'image/gif'                 => imagegif($image, $targetPath),
            'image/webp'                => function_exists('imagewebp')
                ? imagewebp($image, $targetPath, 90)
                : throw new RuntimeException('WEBP wird von GD auf diesem Server nicht unterstützt.'),
            default                     => throw new RuntimeException('Nicht unterstütztes Bildformat: ' . $mime),
        };
    }

    private function insertMediaRecord(
        int $productId,
        string $fileName,
        string $fileType,
        string $pathStandard,
        string $pathSmall,
        string $pathMobile,
        string $pathInvoice
    ): void {
        $userId = (int) Factory::getApplication()->getIdentity()->id;
        $created = Factory::getDate()->toSql();

        $query = $this->db->getQuery(true)
            ->select([
                'COUNT(*) AS media_count',
                'COALESCE(MAX(' . $this->db->quoteName('ordering') . '), 0) AS max_ordering',
            ])
            ->from($this->db->quoteName('#__fdshop_media'))
            ->where($this->db->quoteName('product_id') . ' = ' . (int) $productId);

        $this->db->setQuery($query);
        $stats = (array) $this->db->loadAssoc();

        $isPrimary = ((int) ($stats['media_count'] ?? 0) === 0) ? 1 : 0;
        $ordering = (int) ($stats['max_ordering'] ?? 0) + 1;

        $insertQuery = $this->db->getQuery(true)
            ->insert($this->db->quoteName('#__fdshop_media'))
            ->columns([
                $this->db->quoteName('product_id'),
                $this->db->quoteName('file_name'),
                $this->db->quoteName('file_type'),
                $this->db->quoteName('path_standard'),
                $this->db->quoteName('path_small'),
                $this->db->quoteName('path_mobile'),
                $this->db->quoteName('path_invoice'),
                $this->db->quoteName('is_primary'),
                $this->db->quoteName('ordering'),
                $this->db->quoteName('created'),
                $this->db->quoteName('created_by'),
            ])
            ->values(
                implode(', ', [
                    (int) $productId,
                    $this->db->quote($fileName),
                    $this->db->quote($fileType),
                    $this->db->quote($pathStandard),
                    $this->db->quote($pathSmall),
                    $this->db->quote($pathMobile),
                    $this->db->quote($pathInvoice),
                    (int) $isPrimary,
                    (int) $ordering,
                    $this->db->quote($created),
                    (int) $userId,
                ])
            );

        $this->db->setQuery($insertQuery)->execute();
    }

    private function getExtensionForMime(string $mime): string
    {
        return match ($mime) {
            'image/jpeg', 'image/pjpeg' => 'jpg',
            'image/png'                 => 'png',
            'image/gif'                 => 'gif',
            'image/webp'                => 'webp',
            default                     => throw new RuntimeException('Nicht unterstütztes Bildformat: ' . $mime),
        };
    }

    private function normalizeIds($ids): array
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter(
            $ids,
            static fn (int $id): bool => $id > 0

        );

        return array_values(array_unique($ids));
    }
}