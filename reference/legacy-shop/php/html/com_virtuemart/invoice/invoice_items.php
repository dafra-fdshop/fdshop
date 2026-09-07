<?php
/**
 * Order items view (Invoice/PDF) — final minimal version
 * - Bilder aus #__virtuemart_medias (thumb bevorzugt, dann full)
 * - Wenn vorhanden, bevorzugt .webp-Variante der Datei
 * - Fallback: VirtueMart noimage
 * - Produktname ohne Link
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

$fdAutoCouponEnabled = false;
$fdAutoCouponLabel   = '';

if (PluginHelper::isEnabled('system', 'fdautocoupon')) {
    $p = PluginHelper::getPlugin('system', 'fdautocoupon');
    $r = new Registry($p->params);

    $fdAutoCouponEnabled = (bool) $r->get('enabled', 0);
    $fdAutoCouponLabel   = trim((string) $r->get('display_label', ''));
}

$discountsBill = $this->discountsBill;
$taxBill       = $this->taxBill;

// Bildbreite für die Rechnung (klein halten)
$invoiceImgWidth = 35;

// Für PDF/Mail absolute URLs aktiv (falls VM-Funktionen beteiligt sind)
VirtueMartModelCustomfields::$useAbsUrls = ($this->isMail or $this->isPdf);

// ---------------------------------------------------------
// Helper-Funktionen
// ---------------------------------------------------------

// absolute URL generieren (sauber für Leer-/Sonderzeichen)
function fd_abs_url_from_rel($relPath) {
    $relPath = ltrim($relPath, '/');
    $parts = array_map('rawurlencode', explode('/', $relPath));
    return JURI::root() . implode('/', $parts);
}

// <img>-Tag mit absoluter URL für PDF
function fd_img_tag_abs($relPath, $alt = '', $w = 45) {
    $src = fd_abs_url_from_rel($relPath);
    $alt = htmlspecialchars($alt ?: 'Image', ENT_QUOTES, 'UTF-8');
    return '<img src="'.$src.'" alt="'.$alt.'" style="width:'.$w.'px;height:auto;display:block;margin:0 auto;">';
}

// noimage ermitteln
function fd_get_no_pic_relpath() {
    $candidates = array(
        'components/com_virtuemart/assets/images/vmgeneral/noimage_new.png',
        'components/com_virtuemart/assets/images/vmgeneral/noimage_new.gif',
        'components/com_virtuemart/assets/images/vmgeneral/noimage.png',
        'components/com_virtuemart/assets/images/vmgeneral/noimage.gif',
        'components/com_virtuemart/assets/images/vmgeneral/noimage.jpg',
    );
    foreach ($candidates as $rel) {
        if (is_file(JPATH_ROOT . '/' . $rel)) return $rel;
    }
    return 'components/com_virtuemart/assets/images/vmgeneral/noimage.gif';
}

// Prüft, ob es eine .webp-Zwillingsdatei zum gegebenen Pfad gibt; wenn ja, liefere diese zurück
function fd_prefer_webp_or_original($relPath) {
    if (!$relPath) return null;
    $relPath = ltrim($relPath, '/');

    // Wenn schon webp, direkt zurück
    if (preg_match('/\.webp$/i', $relPath)) {
        return $relPath;
    }

    // Kandidat .webp im selben Ordner
    $webpRel = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $relPath);
    if ($webpRel !== $relPath && is_file(JPATH_ROOT . '/' . $webpRel)) {
        return $webpRel; // nur wenn die Datei tatsächlich existiert
    }

    // Sonst Original (auch wenn per Filesystem nicht verifizierbar — z. B. durch Server-Rewrite)
    return $relPath;
}

// Bild via media_id(s) aus #__virtuemart_medias (Thumb bevorzugt, dann Full; jeweils erst .webp versuchen)
function fd_image_rel_from_media_ids($mediaIds) {
    if (!is_array($mediaIds) || empty($mediaIds)) return null;

    $db  = JFactory::getDbo();
    $ids = array_values(array_unique(array_map('intval', $mediaIds)));
    if (empty($ids)) return null;

    foreach ($ids as $mid) {
        $q = $db->getQuery(true)
            ->select(array($db->qn('file_url_thumb'), $db->qn('file_url')))
            ->from($db->qn('#__virtuemart_medias'))
            ->where($db->qn('virtuemart_media_id') . '=' . (int)$mid)
            ->setLimit(1);
        $db->setQuery($q);
        $row = $db->loadObject();
        if (!$row) continue;

        $thumb = $row->file_url_thumb ? ltrim($row->file_url_thumb, '/') : '';
        $full  = $row->file_url ? ltrim($row->file_url, '/') : '';

        // Reihenfolge: Thumb.webp ? Thumb ? Full.webp ? Full
        $candidates = array();
        if ($thumb) {
            $candidates[] = fd_prefer_webp_or_original($thumb); // liefert ggf. webp oder original
            // Falls prefer_webp auf original zurückfiel und Datei unplausibel leer wäre, bleibt es trotzdem ein Kandidat
        }
        if ($full) {
            $candidates[] = fd_prefer_webp_or_original($full);
        }

        foreach ($candidates as $rel) {
            if (!$rel) continue;
            // Für WebP verlangen wir echte Datei; für Original lassen wir auch "virtuellen" Pfad zu (Rewrite/CDN)
            $isWebp = (bool)preg_match('/\.webp$/i', $rel);
            if (($isWebp && is_file(JPATH_ROOT . '/' . $rel)) || (!$isWebp && !empty($rel))) {
                return $rel;
            }
        }
    }
    return null;
}

// ---------------------------------------------------------
// Tabelle: Kopfzeile
// ---------------------------------------------------------
?>
<table style="width:100%;">
    <tr style="line-height:2px;">
        <td style="text-align:center;width:8%;"><strong><?php echo vmText::_('Bild'); ?></strong></td>
        <td style="text-align:left;width:10%;"><strong><?php echo vmText::_('Art.-Nr.'); ?></strong></td>
        <td style="text-align:left;width:37%;" colspan="2"><strong><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_NAME_TITLE'); ?></strong></td>
        <td style="text-align:right;width:10%;"><strong><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_PRICE'); ?></strong></td>
        <td style="text-align:right;width:8%;"><strong><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_QTY'); ?></strong></td>
        <td style="text-align:right;width:10%;"><strong><?php echo vmText::_('Rabatt'); ?></strong></td>
        <td style="text-align:right;width:12%;"><strong><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_TOTAL'); ?></strong></td>
    </tr>
    <hr noshade size=1>

<?php
foreach ($this->orderDetails['items'] as $item) {
    $qtt          = (int)$item->product_quantity;
    $product_name = htmlspecialchars($item->order_item_name, ENT_QUOTES, 'UTF-8');

    // Bildpfad: media_id -> sonst noimage
    $relFound = null;
    if (!empty($item->virtuemart_media_id)) {
        $mediaIds = is_array($item->virtuemart_media_id) ? $item->virtuemart_media_id : array($item->virtuemart_media_id);
        $relFound = fd_image_rel_from_media_ids($mediaIds);
    }
    if (!$relFound) {
        $relFound = fd_get_no_pic_relpath();
    }

    $imageHtml = fd_img_tag_abs($relFound, $product_name, $invoiceImgWidth);
?>
    <tr style="line-height:6px;">
        <!-- Bild -->
        <td style="text-align:center;"><?php echo $imageHtml; ?></td>

        <!-- SKU -->
        <td style="text-align:left;">&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>
		<?php echo $item->order_item_sku; ?>
		</td>

        <!-- Produktname und Attribute (ohne Link) -->
        <td style="text-align:left;" colspan="2">&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>
            <div><?php echo $product_name; ?></div>
            <?php echo VirtueMartModelCustomfields::CustomsFieldOrderDisplay($item,'FE'); ?>
        </td>

    <?php if ($this->doctype == 'invoice') { ?>
        <td style="text-align:right;" class="priceCol">&nbsp;<br/>&nbsp;<br/>
            <?php
            $item->product_discountedPriceWithoutTax = (float)$item->product_discountedPriceWithoutTax;
            if (!empty($item->product_priceWithoutTax) && $item->product_discountedPriceWithoutTax != $item->product_priceWithoutTax) {
                echo '<span style="text-decoration:line-through; color:#FF0000;">'.$this->currency->priceDisplay($item->product_basePriceWithTax,$this->user_currency_id).'</span><br/>&nbsp;<br/>';
                echo '<span>'.$this->currency->priceDisplay($item->product_final_price,$this->user_currency_id).'</span>';
            } else {
                echo '<span>&nbsp;<br/>'.$this->currency->priceDisplay($item->product_final_price,$this->user_currency_id).'</span>';
            }
            ?>
        </td>
    <?php } ?>

        <td style="text-align:right;">&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>
		<?php echo $qtt; ?>
        </td>

    <?php if ($this->doctype == 'invoice') { ?>
        <td style="text-align:right;" class="priceCol">&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>
            <?php echo $this->currency->priceDisplay($item->product_subtotal_discount,$this->user_currency_id); ?>
        </td>
        <td style="text-align:right;" class="priceCol">&nbsp;<br/>&nbsp;<br/>
            <?php
            $item->product_basePriceWithTax = (float)$item->product_basePriceWithTax;
            if (!empty($item->product_basePriceWithTax) && $item->product_basePriceWithTax != $item->product_final_price) {
                echo '<span style="text-decoration:line-through; color:#FF0000;">'.$this->currency->priceDisplay($item->product_basePriceWithTax,$this->user_currency_id,$qtt).'</span><br/>';
            } elseif (empty($item->product_basePriceWithTax) && $item->product_item_price != $item->product_final_price) {
                echo '<span style="text-decoration:line-through; color:#FF0000;">'.$this->currency->priceDisplay($item->product_item_price,$this->user_currency_id,$qtt).'</span><br/>';
            }
            	echo '<span>&nbsp;<br/>'. $this->currency->priceDisplay($item->product_subtotal_with_tax,$this->user_currency_id).'</span>';
            ?>
        </td>
    <?php } ?>
    </tr>
    <hr noshade size=1>
<?php } ?>
	
    <tr>
        <td colspan="8" style="height:30px;">&nbsp;</td>
    </tr>

<?php if ($this->doctype == 'invoice') { ?>
    <tr class="sectiontableentry1">
        <td colspan="7" style="text-align:right;"><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_PRODUCT_PRICES_TOTAL'); ?></td>
        <td style="text-align:right;"><?php echo $this->currency->priceDisplay($this->orderDetails['details']['BT']->order_salesPrice,$this->user_currency_id); ?></td>
    </tr>
	
	<?php
	if (
		(float)$this->orderDetails['details']['BT']->coupon_discount != 0.0
		|| !empty($this->orderDetails['details']['BT']->coupon_code)
	) {
	?>
		<tr>
			<td colspan="7" style="text-align:right;">
				<?php
				if ($fdAutoCouponEnabled && $fdAutoCouponLabel !== '') {
					echo $fdAutoCouponLabel;
				} else {
					$couponCode = !empty($this->orderDetails['details']['BT']->coupon_code)
						? ' (' . $this->orderDetails['details']['BT']->coupon_code . ')'
						: '';

					echo vmText::_('COM_VIRTUEMART_COUPON_DISCOUNT') . $couponCode;
				}
				?>
			</td>
			<td style="text-align:right;">
				<?php echo $this->currency->priceDisplay($this->orderDetails['details']['BT']->coupon_discount, $this->user_currency_id); ?>
			</td>
		</tr>
	<?php } ?>

    <tr>
        <td style="text-align:right;" colspan="7"><?php echo $this->orderDetails['shipmentName']; ?></td>
        <td style="text-align:right;"><?php echo $this->currency->priceDisplay($this->orderDetails['details']['BT']->order_shipment + $this->orderDetails['details']['BT']->order_shipment_tax,$this->user_currency_id); ?></td>
    </tr>

    <tr>
        <td style="text-align:right;" colspan="7"><?php echo $this->orderDetails['paymentName']; ?></td>
        <td style="text-align:right;"><?php echo $this->currency->priceDisplay($this->orderDetails['details']['BT']->order_payment + $this->orderDetails['details']['BT']->order_payment_tax,$this->user_currency_id); ?></td>
    </tr>

    <tr>
        <td style="text-align:right;" colspan="7"><strong><?php echo vmText::_('COM_VIRTUEMART_ORDER_PRINT_TOTAL'); ?></strong></td>
        <td style="text-align:right;"><strong><?php echo $this->currency->priceDisplay($this->orderDetails['details']['BT']->order_total,$this->user_currency_id); ?></strong></td>
    </tr>
<?php } ?>
</table>
