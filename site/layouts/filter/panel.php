<?php

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;

$facets = $displayData['facets'] ?? [];
$active = $displayData['active'] ?? [];
$categoryId = (int) ($displayData['category_id'] ?? 0);
$suffix = (string) ($displayData['suffix'] ?? 'desktop');
$sort = (string) ($displayData['sort'] ?? 'name');
$direction = (string) ($displayData['direction'] ?? 'asc');
$limit = (int) ($displayData['limit'] ?? 24);
?>
<form class="fdshop-filter" method="get" data-fdshop-filter-form data-filter-instance="<?php echo $suffix; ?>">
    <input type="hidden" name="option" value="com_fdshop">
    <input type="hidden" name="view" value="category">
    <input type="hidden" name="id" value="<?php echo $categoryId; ?>">
    <h2 class="fdshop-filter__title">Filterfunktion</h2>
    <?php foreach ($facets as $key => $facet) : ?>
        <details class="fdshop-filter__group" open>
            <summary><?php echo htmlspecialchars((string) $facet['definition']->label, ENT_QUOTES, 'UTF-8'); ?></summary>
            <div class="fdshop-filter__options">
                <?php foreach ($facet['options'] as $option) : ?>
                    <?php
                    $checked = in_array((string) $option['value'], array_map('strval', $active[$key] ?? []), true);
                    $disabled = (int) $option['count'] === 0 && !$checked;
                    $id = 'fdshop-filter-' . $suffix . '-' . $key . '-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) $option['value']);
                    ?>
                    <label for="<?php echo $id; ?>" class="fdshop-filter__option<?php echo $disabled ? ' is-disabled' : ''; ?>">
                        <input id="<?php echo $id; ?>" type="checkbox" name="fd_filter[<?php echo $key; ?>][]" value="<?php echo htmlspecialchars((string) $option['value'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $checked ? ' checked' : ''; ?><?php echo $disabled ? ' disabled' : ''; ?>>
                        <span><?php echo htmlspecialchars((string) $option['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <small>(<?php echo (int) $option['count']; ?>)</small>
                    </label>
                <?php endforeach; ?>
                <?php if ($facet['options'] === []) : ?><p class="fdshop-filter__empty">Keine Optionen in dieser Kategorie.</p><?php endif; ?>
            </div>
        </details>
    <?php endforeach; ?>
    <button type="button" class="btn btn-link fdshop-filter__reset" data-fdshop-filter-reset>Alle Filter zurücksetzen</button>
    <noscript><button type="submit" class="btn btn-primary">Filter anwenden</button></noscript>
</form>
