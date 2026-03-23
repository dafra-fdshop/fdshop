<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;
?>

<div class="com-fdshop-products">
    <p>
        <a class="btn btn-primary" href="index.php?option=com_fdshop&view=product&layout=edit">
            Neu
        </a>
    </p>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Produktname</th>
                <th>SKU</th>
                <th>Hersteller-ID</th>
                <th>Aktiv</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($this->items)) : ?>
                <?php foreach ($this->items as $item) : ?>
                    <tr>
                        <td><?php echo (int) $item->id; ?></td>
                        <td>
                            <a href="index.php?option=com_fdshop&view=product&layout=edit&id=<?php echo (int) $item->id; ?>">
                                <?php echo htmlspecialchars((string) $item->product_name, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars((string) $item->sku, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int) $item->manufacturer_id; ?></td>
                        <td><?php echo (int) $item->is_active; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5">Noch keine Produkte vorhanden.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>