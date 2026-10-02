<?php
defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

$data = $this->data;
$items = $data['items'];
$state = $data['state'];
$root = rtrim(Uri::root(true), '/');
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$number = static fn($value, $suffix = '') => trim((string) $value) !== '' && (float) $value !== 0.0 ? htmlspecialchars((string) $value) . $suffix : '–';
$availability = static fn($value) => match ((string) $value) {
    'available' => 'Verfügbar',
    'low_stock' => 'Wenige verfügbar',
    'sold_out' => 'Ausverkauft',
    'preorder' => 'Vorbestellbar',
    default => (string) $value ?: '–',
};
$rows = [
    'Hersteller' => fn($product) => $product->manufacturer_name ?: '–',
    'Preis' => fn($product) => number_format((int) $product->discount_active && $product->discount_price > 0 ? (float) $product->discount_price : (float) $product->sale_price, 2, ',', '.') . ' ' . $product->currency,
    'Verfügbarkeit' => fn($product) => $availability($product->in_stock),
    'NEM' => fn($product) => $number($product->nem, ' g'),
    'Schusszahl' => fn($product) => $number($product->shot_count),
    'Kaliber' => fn($product) => $number($product->caliber),
    'Brenndauer' => fn($product) => $number($product->burn_time),
    'Steighöhe' => fn($product) => $number($product->rise_height),
];
?>
<main class="fdshop-comparison-page" data-comparison-page data-token="<?php echo Session::getFormToken(); ?>">
    <header><h1>Produktvergleich</h1><p><?php echo count($items); ?> von <?php echo (int) $state['max']; ?> Produkten ausgewählt</p></header>
    <?php if (!$items) : ?>
        <div class="fdshop-comparison-empty"><h2>Noch keine Produkte ausgewählt</h2><p>Nutze „Vergleichen“ an Produkten derselben Kategorie.</p></div>
    <?php else : ?>
        <div class="fdshop-comparison-tools"><label><input type="checkbox" data-comparison-differences> Nur Unterschiede anzeigen</label><button class="btn btn-outline-secondary" type="button" data-comparison-clear>Vergleich leeren</button></div>
        <div class="fdshop-comparison-scroll"><div class="fdshop-comparison-grid" style="--compare-count:<?php echo count($items); ?>">
            <div class="fdshop-comparison-label fdshop-comparison-label--head">Produkt</div>
            <?php foreach ($items as $product) : ?>
                <article class="fdshop-comparison-product" data-product-id="<?php echo (int) $product->id; ?>">
                    <?php $image = $product->image ? $root . '/' . ltrim($product->image, '/') : $root . '/media/com_fdshop/images/product-placeholder.svg'; ?>
                    <img src="<?php echo $escape($image); ?>" alt="<?php echo $escape($product->product_name); ?>">
                    <div class="fdshop-comparison-product__title"><strong><?php echo $escape($product->product_name); ?></strong><?php if ($product->video) : ?><button class="btn btn-dark btn-sm" type="button" data-fdshop-video="<?php echo $escape($product->video); ?>" data-product-name="<?php echo $escape($product->product_name); ?>" aria-label="Produktvideo zu <?php echo $escape($product->product_name); ?> ansehen" title="Produktvideo ansehen"><i class="fa-solid fa-video" aria-hidden="true"></i></button><?php endif; ?></div>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-comparison-remove="<?php echo (int) $product->id; ?>">Entfernen</button>
                </article>
            <?php endforeach; ?>
            <?php foreach ($rows as $label => $value) : $values = array_map($value, $items); ?>
                <div class="fdshop-comparison-row-label" data-compare-row="<?php echo $escape($label); ?>"><?php echo $escape($label); ?></div>
                <?php foreach ($values as $cell) : ?><div class="fdshop-comparison-value" data-compare-value="<?php echo $escape($cell); ?>"><?php echo $escape($cell); ?></div><?php endforeach; ?>
            <?php endforeach; ?>
        </div></div>
    <?php endif; ?>
    <section class="fdshop-comparison-saved"><h2>Gespeicherte Vergleiche</h2>
        <?php if (!$data['user_id']) : ?><p>Zum dauerhaften Speichern bitte <a href="<?php echo Route::_('index.php?option=com_users&view=login'); ?>">anmelden</a> oder <a href="<?php echo Route::_('index.php?option=com_users&view=registration'); ?>">registrieren</a>. Dein aktiver Vergleich bleibt in dieser Sitzung erhalten.</p>
        <?php else : ?><form data-comparison-save><label>Name <input name="name" maxlength="100" required></label><button class="btn btn-secondary">Aktuellen Vergleich speichern</button></form><div data-comparison-saved>
            <?php foreach ($data['saved'] as $list) : ?><article data-comparison-list="<?php echo (int) $list->id; ?>"><strong><?php echo $escape($list->name); ?></strong><small><?php echo (int) $list->product_count; ?> Produkte</small><div class="fdshop-comparison-saved__actions"><button class="btn btn-sm btn-primary" type="button" data-comparison-activate>Öffnen</button><button class="btn btn-sm btn-outline-secondary" type="button" data-comparison-rename>Umbenennen</button><button class="btn btn-sm btn-outline-danger" type="button" data-comparison-delete>Löschen</button></div></article><?php endforeach; ?>
        </div><?php endif; ?>
    </section>
    <dialog class="fdshop-video" data-fdshop-video-dialog aria-labelledby="fdshop-comparison-video-title"><div class="fdshop-video__header"><h2 id="fdshop-comparison-video-title" data-fdshop-video-title>Produktvideo</h2><button type="button" class="fdshop-video__close" data-fdshop-video-close aria-label="Video schließen">×</button></div><div class="fdshop-video__content" data-fdshop-video-content></div></dialog>
</main>
