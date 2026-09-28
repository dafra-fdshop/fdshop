<?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
$guest=Factory::getApplication()->getIdentity()->guest;
Factory::getApplication()->getLanguage()->load('com_fdshop', JPATH_ADMINISTRATOR);
$return=base64_encode(Uri::getInstance()->toString());
?>
<dialog class="fdshop-watch-dialog" data-watch-dialog data-watch-url="<?php echo htmlspecialchars(Route::_('index.php?option=com_fdshop&format=json&task=interaction.watch',false),ENT_QUOTES,'UTF-8'); ?>"><div class="fdshop-watch-dialog__content"><button type="button" class="fdshop-watch-dialog__close" data-watch-close aria-label="<?php echo Text::_('COM_FDSHOP_WATCH_CLOSE'); ?>">×</button><h2><?php echo Text::_($guest?'COM_FDSHOP_WATCH_TITLE_GUEST':'COM_FDSHOP_WATCH_TITLE_USER'); ?></h2><p data-watch-product></p>
<?php if($guest): ?><p><?php echo Text::_('COM_FDSHOP_WATCH_GUEST_NOTE'); ?></p><div class="fdshop-watch-dialog__actions"><a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_users&view=login&return='.rawurlencode($return)); ?>"><?php echo Text::_('COM_FDSHOP_WATCH_LOGIN'); ?></a><a class="btn btn-outline-primary" href="<?php echo Route::_('index.php?option=com_users&view=registration&return='.rawurlencode($return)); ?>"><?php echo Text::_('COM_FDSHOP_WATCH_REGISTER'); ?></a><button type="button" class="btn btn-secondary" data-watch-close><?php echo Text::_('COM_FDSHOP_WATCH_CLOSE'); ?></button></div>
<?php else: ?><p><?php echo Text::_('COM_FDSHOP_WATCH_USER_NOTE'); ?></p><p class="fdshop-watch-dialog__message" data-watch-message role="status"></p><div class="fdshop-watch-dialog__actions"><button type="button" class="btn btn-secondary" data-watch-close><?php echo Text::_('COM_FDSHOP_WATCH_CANCEL'); ?></button><button type="button" class="btn btn-primary" data-watch-activate><?php echo Text::_('COM_FDSHOP_WATCH_ACTIVATE'); ?></button></div><form hidden data-watch-token><?php echo HTMLHelper::_('form.token'); ?></form><?php endif; ?></div></dialog>
