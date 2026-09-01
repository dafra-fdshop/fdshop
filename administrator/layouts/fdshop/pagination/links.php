<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_fdshop
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$list = $displayData['list'];
$options = $displayData['options'];
$pages = $list['pages'];
$total = (int) $list['total'];
$formId = (string) ($options['formId'] ?? 'adminForm');
$scope = (string) ($options['scope'] ?? '');
$fieldName = $scope . '[limitstart]';

$renderLink = static function (array $page) use ($formId, $fieldName): string {
    $item = $page['data'];
    $text = (string) $item->text;
    $icon = match ($text) {
        Text::_('JLIB_HTML_START') => 'icon-angle-double-left',
        Text::_('JPREV')           => 'icon-angle-left',
        Text::_('JNEXT')           => 'icon-angle-right',
        Text::_('JLIB_HTML_END')   => 'icon-angle-double-right',
        default                    => null,
    };
    $display = $icon === null ? htmlspecialchars($text, ENT_QUOTES, 'UTF-8') : '<span class="' . $icon . '" aria-hidden="true"></span>';

    if (!$page['active']) {
        $class = !empty($item->active) ? 'active' : 'disabled';

        return '<li class="page-item ' . $class . '"><span class="page-link">' . $display . '</span></li>';
    }

    $offset = max(0, (int) $item->base);
    $form = json_encode($formId, JSON_HEX_APOS | JSON_HEX_QUOT);
    $field = json_encode($fieldName, JSON_HEX_APOS | JSON_HEX_QUOT);
    $onclick = 'const form=document.getElementById(' . $form . ');form.elements[' . $field . '].value=' . $offset . ';Joomla.submitform(\'\',form);return false;';

    return '<li class="page-item"><a href="#" class="page-link" onclick="' . htmlspecialchars($onclick, ENT_QUOTES, 'UTF-8') . '">' . $display . '</a></li>';
};

$currentPage = 1;

foreach ($pages['pages'] ?? [] as $number => $page) {
    if (!$page['active']) {
        $currentPage = (int) $number;
    }
}

$first = $total > 0 ? (($currentPage - 1) * (int) $list['limit']) + 1 : 0;
$last = min($total, $first + (int) $list['limit'] - 1);
?>
<nav class="pagination__wrapper" aria-label="<?php echo Text::_('JLIB_HTML_PAGINATION'); ?>">
    <div class="text-end me-3">
        <?php echo Text::sprintf('JLIB_HTML_PAGINATION_NUMBERS', $first, $last, $total); ?>
    </div>
    <?php if (!empty($pages)) : ?>
        <div class="pagination pagination-toolbar text-center mt-0">
            <ul class="pagination ms-auto me-0">
                <?php echo $renderLink($pages['start']); ?>
                <?php echo $renderLink($pages['previous']); ?>
                <?php foreach ($pages['pages'] as $page) : ?>
                    <?php echo $renderLink($page); ?>
                <?php endforeach; ?>
                <?php echo $renderLink($pages['next']); ?>
                <?php echo $renderLink($pages['end']); ?>
            </ul>
        </div>
    <?php endif; ?>
    <input type="hidden" name="<?php echo htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo (int) $list['limitstart']; ?>">
</nav>
