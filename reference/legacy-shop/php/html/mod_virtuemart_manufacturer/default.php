<?php
defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

$cls = trim($params->get('moduleclass_sfx', ''));
?>
<div class="vm-manus<?php echo $cls ? ' ' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') : ''; ?>">

  <?php if (!empty($headerText)) : ?>
    <div class="vmheader"><?php echo $headerText; ?></div>
  <?php endif; ?>

  <div class="vm-manus__grid">
    <?php foreach ($manufacturers as $m) :
      $link = Route::_('index.php?option=com_virtuemart&view=manufacturer&virtuemart_manufacturer_id=' . (int) $m->virtuemart_manufacturer_id);
      ?>
      <a class="vm-manus__item"
		   href="<?php echo $link; ?>"
		   title="Weitere Informationen zu <?php echo htmlspecialchars($m->mf_name, ENT_QUOTES, 'UTF-8'); ?>"
		   aria-label="Weitere Informationen zu <?php echo htmlspecialchars($m->mf_name, ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (!empty($m->images) && ($show === 'image' || $show === 'all')) : ?>
          <span class="vm-manus__img">
            <?php echo $m->images[0]->displayMediaThumb('', false); ?>
          </span>
        <?php endif; ?>

        <?php if ($show === 'text' || $show === 'all') : ?>
          <span class="vm-manus__txt"><?php echo htmlspecialchars($m->mf_name ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (!empty($footerText)) : ?>
    <div class="vmfooter<?php echo $cls ? ' ' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') : ''; ?>">
      <?php echo $footerText; ?>
    </div>
  <?php endif; ?>

</div>