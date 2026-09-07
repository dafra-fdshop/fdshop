<?php
defined('_JEXEC') or die;

/**
 * Custom Shopper Mail: Zahlung eingegangen (minimal)
 * Datei: mail_zahlungbest.php
 * Ort: templates/DEIN_TEMPLATE/html/com_virtuemart/invoice/
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text as JText;
use Joomla\CMS\Router\Route;                 
use Joomla\CMS\Uri\Uri;                   
use Joomla\CMS\HTML\HTMLHelper as JHtml;

$orderBT     = $this->orderDetails['details']['BT'] ?? null;
$orderNumber = $orderBT->order_number ?? '';
$orderId     = (int)($orderBT->virtuemart_order_id ?? 0);
$orderPass   = $orderBT->order_pass ?? '';
$orderDate   = $orderBT->created_on ?? '';
$orderTotal  = $orderBT->order_total ?? '';
$orderCur    = $orderBT->order_currency ?? '';
$shipmentName = $this->orderDetails['shipmentName'] ?? '';

$shopName = \VmConfig::get('shop_name', Factory::getConfig()->get('sitename'));

// Logo-URL absolut aufbauen (typischer Medienpfad)
// WICHTIG: Für E-Mails MUSS die URL öffentlich erreichbar sein.
$logoPathRel = 'images/logo/logo-website.png';
$logoUrl     = rtrim(Uri::root(), '/') . '/' . ltrim($logoPathRel, '/');

// Schöne Datumsformatierung (Joomla-Format)
$formatDate = function($dateStr) {
    if (empty($dateStr)) return '';
    return JHtml::_('date', $dateStr, JText::_('DATE_FORMAT_LC2'));
};

// Preis formatieren (VM-Currency bevorzugt)
if (isset($this->currency) && is_object($this->currency)) {
    $priceHtml = $this->currency->priceDisplay($orderTotal);
} else {
    $priceHtml = number_format((float)$orderTotal, 2, ',', '.') . ' ' . htmlspecialchars($orderCur);
}


// Optionaler Link zur Bestellansicht (falls VM dies erlaubt)

$viewOrderUrl = '';
if ($orderId && $orderPass) {
    $viewOrderUrl = JRoute::_(
        "index.php?option=com_virtuemart&view=orders&layout=details&order_number={$orderNumber}&order_pass={$orderPass}",
        false
    );
    if (strpos($viewOrderUrl, 'http') !== 0) {
        $viewOrderUrl = rtrim(JUri::root(), '/') . '/' . ltrim($viewOrderUrl, '/');
    }
}

// Optionaler Link zur Bestellansicht (Frontend-URL erzwingen)
$viewOrderUrl = '';
if (!empty($orderNumber) && !empty($orderPass)) {
    // (Optional) passendes Itemid für saubere SEF/Module/Language
    $itemId = (int) shopFunctionsF::getMenuItemId($orderBT->order_language ?? '');

    $query = 'index.php?option=com_virtuemart&view=orders&layout=details'
           . '&order_number=' . rawurlencode($orderNumber)
           . '&order_pass='   . rawurlencode($orderPass)
           . ($itemId ? '&Itemid=' . $itemId : '');

    // WICHTIG: Route über den **Site**-Client + absolute URL
    $viewOrderUrl = Route::link('site', $query, false, Route::TLS_IGNORE, true);
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?php echo htmlspecialchars($shopName); ?> – Zahlung eingegangen</title>
</head>
<body style="margin:0; padding:0; background:#f6f7fb; font-family:Arial, Helvetica, sans-serif; color:#222;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f6f7fb; padding:20px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:100%; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
          
          <!-- LOGO (zentriert) -->
          <tr>
            <td align="center" style="padding:22px 24px 10px; background:#ffffff;">
              <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="<?php echo htmlspecialchars($shopName); ?>" style="display:block; max-width:220px; height:auto;">
            </td>
          </tr>

          <!-- Headerleiste -->
          <tr>
            <td style="background:#111827; padding:14px 24px;">
              <h1 style="margin:0; font-size:18px; line-height:1.3; color:#ffffff;">
                <?php echo htmlspecialchars($shopName); ?>
              </h1>
            </td>
          </tr>

          <!-- Intro / Text -->
          <tr>
            <td style="padding:24px;">
              <p style="margin:0 0 12px; font-size:16px; line-height:1.5;">
                <strong>Bestell- und Zahlungsbestätigung zu Ihrer Bestellung <?php echo htmlspecialchars($orderNumber); ?>.</strong>
              </p>
              <p style="margin:0 0 12px; font-size:14px; line-height:1.6;">
                Vielen Dank für Ihre Zahlung! Die <strong>Rechnung</strong> haben wir dieser E-Mail als <strong>PDF</strong> beigefügt.
              </p>
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse; margin-top:6px;">
                <?php if (!empty($orderDate)) : ?>
                <tr>
                  <td style="padding:8px 0; font-size:14px; border-bottom:1px solid #eee;"><strong>Bestelldatum:</strong></td>
                  <td style="padding:8px 0; font-size:14px; border-bottom:1px solid #eee;" align="right"><?php echo $formatDate($orderDate); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                  <td style="padding:8px 0; font-size:14px; border-bottom:1px solid #eee;"><strong>Gesamtsumme:</strong></td>
                  <td style="padding:8px 0; font-size:14px; border-bottom:1px solid #eee;" align="right"><?php echo $priceHtml; ?></td>
                </tr>
				<?php if (!empty($shipmentName)) : ?>
				<tr>
				  <td style="padding:8px 0; font-size:14px;"><strong>Abholstation:</strong></td>
				  <td style="padding:8px 0; font-size:14px;" align="right">
					<?php echo $shipmentName; ?>
				  </td>
				</tr>
				<?php endif; ?>
              </table>
            </td>
          </tr>

          <!-- Optional: Button zur Bestellansicht -->
          <?php if (!empty($viewOrderUrl)) : ?>
          <tr>
            <td style="padding:0 24px 24px;">
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="left">
                <tr>
                  <td style="border-radius:6px; background:#111827;">
                    <a href="<?php echo htmlspecialchars($viewOrderUrl); ?>"
                       style="display:inline-block; padding:12px 18px; font-size:14px; color:#ffffff; text-decoration:none;">
                      Bestellung online ansehen
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <?php endif; ?>

          <!-- Footer -->
          <tr>
            <td style="padding:12px 24px 24px;">
              <p style="margin:0; font-size:13px; line-height:1.6; color:#666;">
                Bei Fragen antworten Sie einfach auf diese E-Mail. Vielen Dank für Ihren Einkauf!
              </p>
            </td>
          </tr>

          <tr>
            <td style="background:#f3f4f6; padding:14px 24px;">
              <p style="margin:0; font-size:12px; color:#6b7280;">
                © <?php echo date('Y'); ?> <?php echo htmlspecialchars($shopName); ?>
              </p>
            </td>
          </tr>

        </table>

        <p style="margin:14px 0 0; font-size:12px; color:#8b8b8b;">
          Hinweis: Die detaillierte Artikelauflistung finden Sie in der angehängten Rechnung (PDF).
        </p>
      </td>
    </tr>
  </table>
</body>
</html>
