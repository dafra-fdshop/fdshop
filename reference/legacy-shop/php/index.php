<?php
/**
 * @package     Joomla.Site
 * @subpackage  Templates.cassiopeia
 *
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/** @var Joomla\CMS\Document\HtmlDocument $this */

$app   = Factory::getApplication();
$input = $app->getInput();
$wa    = $this->getWebAssetManager();

// Browsers support SVG favicons
$this->addHeadLink(HTMLHelper::_('image', 'joomla-favicon.svg', '', [], true, 1), 'icon', 'rel', ['type' => 'image/svg+xml']);
$this->addHeadLink(HTMLHelper::_('image', 'favicon.ico', '', [], true, 1), 'alternate icon', 'rel', ['type' => 'image/vnd.microsoft.icon']);
$this->addHeadLink(HTMLHelper::_('image', 'joomla-favicon-pinned.svg', '', [], true, 1), 'mask-icon', 'rel', ['color' => '#000']);

// Detecting Active Variables
$option   = $input->getCmd('option', '');
$view     = $input->getCmd('view', '');
$layout   = $input->getCmd('layout', '');
$task     = $input->getCmd('task', '');
$itemid   = $input->getCmd('Itemid', '');
$sitename = htmlspecialchars($app->get('sitename'), ENT_QUOTES, 'UTF-8');

// Original: active MenuItem
$menu      = $app->getMenu()->getActive();
$pageclass = $menu !== null ? $menu->getParams()->get('pageclass_sfx', '') : '';

// Für Startseiten-Check brauchen wir das Menü-OBJEKT
$menuObj = $app->getMenu();

//Für den Check ob es sich um LogIn von com_users dreht
$isUsersLogin = ($option === 'com_users' && $view === 'login');

// Color Theme
$paramsColorName = $this->params->get('colorName', 'colors_standard');
$assetColorName  = 'theme.' . $paramsColorName;

// Use a font scheme if set in the template style options
$paramsFontScheme = $this->params->get('useFontScheme', false);
$fontStyles       = '';

if ($paramsFontScheme) {
    if (stripos($paramsFontScheme, 'https://') === 0) {
        $this->getPreloadManager()->preconnect('https://fonts.googleapis.com/', ['crossorigin' => 'anonymous']);
        $this->getPreloadManager()->preconnect('https://fonts.gstatic.com/', ['crossorigin' => 'anonymous']);
        $this->getPreloadManager()->preload($paramsFontScheme, ['as' => 'style', 'crossorigin' => 'anonymous']);
        $wa->registerAndUseStyle('fontscheme.current', $paramsFontScheme, [], ['rel' => 'lazy-stylesheet', 'crossorigin' => 'anonymous']);

        if (preg_match_all('/family=([^?:]*):/i', $paramsFontScheme, $matches) > 0) {
            $fontStyles = '--cassiopeia-font-family-body: "' . str_replace('+', ' ', $matches[1][0]) . '", sans-serif;
            --cassiopeia-font-family-headings: "' . str_replace('+', ' ', $matches[1][1] ?? $matches[1][0]) . '", sans-serif;
            --cassiopeia-font-weight-normal: 400;
            --cassiopeia-font-weight-headings: 700;';
        }
    } elseif ($paramsFontScheme === 'system') {
        $fontStylesBody    = $this->params->get('systemFontBody', '');
        $fontStylesHeading = $this->params->get('systemFontHeading', '');

        if ($fontStylesBody) {
            $fontStyles = '--cassiopeia-font-family-body: ' . $fontStylesBody . ';
            --cassiopeia-font-weight-normal: 400;';
        }
        if ($fontStylesHeading) {
            $fontStyles .= '--cassiopeia-font-family-headings: ' . $fontStylesHeading . ';
            --cassiopeia-font-weight-headings: 700;';
        }
    } else {
        $wa->registerAndUseStyle('fontscheme.current', $paramsFontScheme, ['version' => 'auto'], ['rel' => 'lazy-stylesheet']);
        $this->getPreloadManager()->preload($wa->getAsset('style', 'fontscheme.current')->getUri() . '?' . $this->getMediaVersion(), ['as' => 'style']);
    }
}

// Enable assets
$wa->usePreset('template.cassiopeia.' . ($this->direction === 'rtl' ? 'rtl' : 'ltr'))
    ->useStyle('template.active.language')
    ->registerAndUseStyle($assetColorName, 'global/' . $paramsColorName . '.css')
    ->useStyle('template.user')
    ->useScript('template.user')
    ->addInlineStyle(":root {
        --hue: 214;
        --template-bg-light: #f0f4fb;
        --template-text-dark: #495057;
        --template-text-light: #ffffff;
        --template-link-color: var(--link-color);
        --template-special-color: #001B4C;
        $fontStyles
    }");

// VM CSS nur auf echten VM-Seiten
if ($option === 'com_virtuemart') {
    $wa->useStyle('template.vm');
}


// Meine ERGÄNZUNG: Bootstrap Carousel nur Startseite
$active  = $menuObj->getActive();
$default = $menuObj->getDefault();

if ($active && $default && (int) $active->id === (int) $default->id) {
    $wa->useScript('bootstrap.carousel');
}

if ($option === 'com_virtuemart' && $view === 'productdetails') {
    $wa->useScript('bootstrap.tab');
}

$wa->useScript('bootstrap.modal');
$wa->useScript('bootstrap.offcanvas');

// Override 'template.active' asset to set correct ltr/rtl dependency
$wa->registerStyle('template.active', '', [], [], ['template.cassiopeia.' . ($this->direction === 'rtl' ? 'rtl' : 'ltr')]);


// Logo file and/or site title
$logoFile  = $this->params->get('logoFile');
$siteTitle = $this->params->get('siteTitle');
$siteDescription = $this->params->get('siteDescription');
	
if ($logoFile && $siteTitle) {
    $logo  = HTMLHelper::_('image', Uri::root(false) . htmlspecialchars($logoFile, ENT_QUOTES), $sitename, ['class' => 'logo-img', 'loading' => 'eager', 'decoding' => 'async'], false, 0);
    $logo .= '<span class="brand-text">';
    $logo .=   '<span class="logo-text" title="' . $sitename . '">'
            .  htmlspecialchars($siteTitle, ENT_COMPAT, 'UTF-8')
            .  '</span>';

    if ($siteDescription) {
        $logo .= '<span class="site-description">' .  htmlspecialchars($siteDescription, ENT_COMPAT, 'UTF-8') .  '</span>';
    }

    $logo .= '</span>';

} elseif ($logoFile) {
    // Nur Logo
    $logo = HTMLHelper::_('image',
        Uri::root(false) . htmlspecialchars($logoFile, ENT_QUOTES), $sitename, ['class' => 'logo-img', 'loading' => 'eager', 'decoding' => 'async'], false, 0);

} elseif ($siteTitle) {
    // Nur Firmenname
    $logo = '<span class="logo-text" title="' . $sitename . '">' . htmlspecialchars($siteTitle, ENT_COMPAT, 'UTF-8') . '</span>';

} else {
    // Fallback
    $logo = HTMLHelper::_('image', 'logo.svg', $sitename, ['class' => 'logo-img d-inline-block', 'loading' => 'eager', 'decoding' => 'async'], true, 0);
}



$hasClass = '';



if ($this->countModules('sidebar-left', true)) {
    $hasClass .= ' has-sidebar-left';
}


if ($this->countModules('sidebar-right', true)) {
    $hasClass .= ' has-sidebar-right';
}



// Container
$wrapper = $this->params->get('fluidContainer') ? 'wrapper-fluid' : 'wrapper-static';

$this->setMetaData('viewport', 'width=device-width, initial-scale=1');

$stickyHeader = $this->params->get('stickyHeader') ? 'position-sticky sticky-top' : '';

// ACHTUNG MUSS AM ENDE KOMMEN DA CF FILTER DIE EIGENEN ÄNDERUNGEN WIEDER ÜBERSCHREIBT
// Defer fontawesome for increased performance. Once the page is loaded javascript changes it to a stylesheet.
$wa->getAsset('style', 'fontawesome')->setAttribute('rel', 'lazy-stylesheet');

// Meine ERGÄNZUNGEN: Advanced Color + Fonts (müssen NACH usePreset rein!)
$wa->registerAndUseStyle('colors_custom', 'global/colors.css')
    ->addInlineStyle(':root {
        --body-bg: ' . $this->params->get('bodybg') . ';
        --body-color: ' . $this->params->get('bodycolor') . ';
        --btnbg: ' . $this->params->get('btnbg') . ';
        --btnbgh: ' . $this->params->get('btnbgh') . ';
        --btncolor: ' . $this->params->get('btncolor') . ';
        --btncolorh: ' . $this->params->get('btncolorh') . ';
        --footerbg: ' . $this->params->get('footerbg') . ';
        --footercolor: ' . $this->params->get('footercolor') . ';
        --headerbg: ' . $this->params->get('headerbg') . ';
        --headercolor: ' . $this->params->get('headercolor') . ';
        --link-color: ' . $this->params->get('linkcolor') . ';
        --link-hover-color: ' . $this->params->get('linkcolorh') . ';
    }')
    ->registerAndUseStyle('font_advanced', 'global/fonts.css')
    ->addInlineStyle(':root {
        --body-font-size: ' . $this->params->get('bodysize') . 'rem;
        --h1size: ' . $this->params->get('h1size') . 'rem;
        --h2size: ' . $this->params->get('h2size') . 'rem;
        --h3size: ' . $this->params->get('h3size') . 'rem;
    }');



?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">

<head>
    <jdoc:include type="metas" />
    <jdoc:include type="styles" />
    <jdoc:include type="scripts" />
</head>

<body id="top" class="site <?php echo $option
    . ' ' . $wrapper
    . ' view-' . $view
    . ($layout ? ' layout-' . $layout : ' no-layout')
    . ($task ? ' task-' . $task : ' no-task')
    . ($itemid ? ' itemid-' . $itemid : '')
    . ($pageclass ? ' ' . $pageclass : '')
    . $hasClass
    . ($this->direction == 'rtl' ? ' rtl' : '');
?>">

    <header class="header container-header full-width<?php echo $stickyHeader ? ' ' . $stickyHeader : ''; ?>">

        <?php if ($this->countModules('topbar')) : ?>
            <div class="container-topbar">
                <jdoc:include type="modules" name="topbar" style="none" />
            </div>
        <?php endif; ?>
		
		

        <?php if ($this->countModules('below-top-actions')) : ?>
		  <div class="grid-child container-below-top">
			<div class="navbar-brand">
			  <a class="brand-logo" href="<?php echo $this->baseurl; ?>/">
				<?php echo $logo; ?>
			  </a>
			</div>

			<div class="belowtop-search">
			   <jdoc:include type="modules" name="below-top-search" style="none" />
			</div>
			
			<div class="belowtop-container">
				<div class="belowtop-actions-top">
				
					<jdoc:include type="modules" name="below-top-contact" style="none" />
				
				</div>
				<div class="belowtop-actions">

				<?php if ($this->countModules('offcanvas-left') 
					   || $this->countModules('offcanvas-left-top') 
					   || $this->countModules('offcanvas-left-bottom')) : ?>

					<button
						class="btn fd-offcanvas-toggle"
						type="button"
						data-bs-toggle="offcanvas"
						data-bs-target="#fdOffcanvasMenu"
						aria-controls="fdOffcanvasMenu"
						aria-label="Menü öffnen">
						<span class="fd-burger" aria-hidden="true"></span>
					</button>

				<?php endif; ?>

				<?php if ($this->countModules('below-top-login')) : ?>
				  <!-- Modal-Trigger-Button -->
				  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
					<i class="fa fa-user" aria-hidden="true"></i> Login
				  </button>

				<?php endif; ?>


				   <jdoc:include type="modules" name="below-top-actions" style="none" />
				</div>
			</div>
		  </div>
		<?php endif; ?>

        
	<div class="container-header-nav">
        <?php if ($this->countModules('menu', true) || $this->countModules('search', true)) : ?>
            <div class="grid-child container-nav">
                <?php if ($this->countModules('menu', true)) : ?>
                    <jdoc:include type="modules" name="menu" style="card" />
                <?php endif; ?>
                <?php if ($this->countModules('search', true)) : ?>
                    <div class="container-search">
                        <jdoc:include type="modules" name="search" style="none" />
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
	</div>
    </header>

    <div class="site-grid">
        <?php if ($this->countModules('banner', true)) : ?>
            <div class="container-banner full-width">
                <jdoc:include type="modules" name="banner" style="none" />
            </div>
        <?php endif; ?>
		
		<jdoc:include type="modules" name="debug" style="none" />
		
		<div class="grid-child container-top-00">
			<div class="system-messages">
				<jdoc:include type="message" />
			</div>
			<?php if ($isUsersLogin) : ?>
				<div class="grid-child container-login-top">
					<jdoc:include type="component" />
				</div>
			<?php endif; ?>
		</div>
		
        <?php if ($this->countModules('top-a', true)) : ?>
            <div class="grid-child container-top-a">
                <jdoc:include type="modules" name="top-a" style="card" />
            </div>
        <?php endif; ?>

        <?php if ($this->countModules('top-b', true)) : ?>
            <div class="grid-child container-top-b">
                <jdoc:include type="modules" name="top-b" style="card" />
            </div>
        <?php endif; ?>
		
		<?php if ($this->countModules('top-c', true)) : ?>
            <div class="grid-child container-top-c">
                <jdoc:include type="modules" name="top-c" style="card" />
            </div>
        <?php endif; ?>

        <?php if ($this->countModules('sidebar-left', true)) : ?>
            <div class="grid-child container-sidebar-left">
                <jdoc:include type="modules" name="sidebar-left" style="card" />
            </div>
        <?php endif; ?>

        <div class="grid-child container-component">
            <jdoc:include type="modules" name="breadcrumbs" style="none" />
            <jdoc:include type="modules" name="main-top" style="card" />
            <jdoc:include type="message" />
            <main>
                <jdoc:include type="component" />
            </main>
            <jdoc:include type="modules" name="main-bottom" style="card" />
        </div>

        <?php if ($this->countModules('sidebar-right', true)) : ?>
            <div class="grid-child container-sidebar-right">
                <jdoc:include type="modules" name="sidebar-right" style="card" />
            </div>
        <?php endif; ?>

        <?php if ($this->countModules('bottom-a', true)) : ?>
            <div class="grid-child container-bottom-a">
                <jdoc:include type="modules" name="bottom-a" style="card" />
            </div>
        <?php endif; ?>

        <?php if ($this->countModules('bottom-b', true)) : ?>
            <div class="grid-child container-bottom-b">
                <jdoc:include type="modules" name="bottom-b" style="card" />
            </div>
        <?php endif; ?>
		
		<?php if ($this->countModules('bottom-c', true)) : ?>
            <div class="grid-child container-bottom-c">
                <jdoc:include type="modules" name="bottom-c" style="card" />
            </div>
        <?php endif; ?>
    </div>

    <?php if ($this->countModules('footer', true)) : ?>
        <footer class="container-footer footer full-width">
            <div class="grid-child">
                <jdoc:include type="modules" name="footer" style="none" />
            </div>
			<?php if ($this->countModules('copyright', true)) : ?>
			<div class="grid-child container-copyright">
                <jdoc:include type="modules" name="copyright" style="none" />
            </div>
			<?php endif; ?>
        </footer>
    <?php endif; ?>

    <?php if ($this->params->get('backTop') == 1) : ?>
        <a href="#top" id="back-top" class="back-to-top-link" aria-label="<?php echo Text::_('TPL_CASSIOPEIA_BACKTOTOP'); ?>">
            <span class="icon-arrow-up icon-fw" aria-hidden="true"></span>
        </a>
    <?php endif; ?>
	
	<div class="modal fade" id="fdModal" tabindex="-1" aria-labelledby="fdModalLabel" aria-hidden="true">
	  <div class="modal-dialog modal-xl modal-dialog-scrollable">
		<div class="modal-content">
		  <div class="modal-header">
			<h2 class="modal-title fs-5" id="fdModalLabel"></h2>
			<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
		  </div>
		  <div class="modal-body p-0">
			<iframe id="fdModalFrame"
					src=""
					loading="lazy"
					style="width:100%; height:70vh; border:0;"
					title=""></iframe>
		  </div>
		</div>
	  </div>
	</div>

	
	 <!-- Modal selbst -->
	<?php if ($this->countModules('below-top-login')) : ?>
	  <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered modal-lg">
		  <div class="modal-content">
			<div class="modal-header">
			  <h5 class="modal-title" id="loginModalLabel">Kunden Bereich</h5>
			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
			</div>
			<div class="modal-body">
			  <jdoc:include type="modules" name="below-top-login" style="noCard" />
			</div>
		  </div>
		</div>
	  </div>
	<?php endif; ?>

	<?php if ($this->countModules('offcanvas-left') || $this->countModules('offcanvas-left-top') || $this->countModules('offcanvas-left-bottom')) : ?>
	  <div class="offcanvas offcanvas-start fd-offcanvas-menu" tabindex="-1" id="fdOffcanvasMenu" aria-labelledby="fdOffcanvasMenuLabel">
		<div class="offcanvas-header">
		  <h5 class="offcanvas-title" id="fdOffcanvasMenuLabel">Menü</h5>
		  <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Schließen"></button>
		</div>

		<div class="offcanvas-body">

		  <?php if ($this->countModules('offcanvas-left-top')) : ?>
			<div class="fd-offcanvas-section fd-offcanvas-top">
			  <jdoc:include type="modules" name="offcanvas-left-top" style="none" />
			</div>
		  <?php endif; ?>

		  <?php if ($this->countModules('offcanvas-left')) : ?>
			<div class="fd-offcanvas-section fd-offcanvas-main">
			  <jdoc:include type="modules" name="offcanvas-left" style="none" />
			</div>
		  <?php endif; ?>

		  <?php if ($this->countModules('offcanvas-left-bottom')) : ?>
			<div class="fd-offcanvas-section fd-offcanvas-bottom">
			  <jdoc:include type="modules" name="offcanvas-left-bottom" style="none" />
			</div>
		  <?php endif; ?>

		</div>
	  </div>
	<?php endif; ?>
</body>
</html>
