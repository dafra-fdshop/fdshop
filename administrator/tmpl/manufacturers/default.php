<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;
?>

<div class="com-fdshop-manufacturers">
	
	<p>
        <a class="btn btn-primary" href="index.php?option=com_fdshop&view=manufacturer&layout=edit">
            Neu
        </a>
    </p>
	
    <table class="table table-striped">
        <thead>
            <tr>
                <th><?php echo htmlspecialchars('Herstellername', ENT_QUOTES, 'UTF-8'); ?></th>
                <th><?php echo htmlspecialchars('Veröffentlicht', ENT_QUOTES, 'UTF-8'); ?></th>
                <th><?php echo htmlspecialchars('ID', ENT_QUOTES, 'UTF-8'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($this->items)) : ?>
                <?php foreach ($this->items as $item) : ?>
                    <tr>
                        <td>
                            <a href="index.php?option=com_fdshop&view=manufacturer&layout=edit&id=<?php echo (int) $item->id; ?>">
                                <?php echo htmlspecialchars((string) $item->manufacturer_name, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </td>
                        <td><?php echo (int) $item->is_active; ?></td>
                        <td><?php echo (int) $item->id; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="3">Noch keine Hersteller vorhanden.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>