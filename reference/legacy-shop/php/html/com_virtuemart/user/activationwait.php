<?php
/**
 *
 * Activation wait screen after checkout registration
 *
 * @package    VirtueMart
 * @subpackage User
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$continueUrl = Route::_('index.php?option=com_virtuemart&view=cart&task=checkout&activationdone=1');
$checkUrl    = Route::_('index.php?option=com_virtuemart&view=user&task=checkActivationStatus&format=json');
$resendUrl   = Route::_('index.php?option=com_virtuemart&view=user&task=resendActivationMail');

?>
<div class="vm-activation-wait">

	<h1>E-Mail-Bestätigung ausstehend</h1>

	<div class="alert alert-info" role="alert">
		<strong>Fast geschafft.</strong><br>
		Ihr Kundenkonto wurde angelegt. Bitte bestätigen Sie jetzt zuerst Ihre E-Mail-Adresse über den Link in der Registrierungs-Mail.<br><br>
		Sobald die Bestätigung erfolgt ist, werden Sie automatisch zurück zum Warenkorb geleitet.
	</div>

	<div class="activation-status-box" id="activation-status-box">
		<p id="activation-status-text">
			Wir prüfen automatisch, ob Ihre E-Mail-Adresse bereits bestätigt wurde.
		</p>

		<p class="small text-muted" id="activation-status-subtext">
			Dies kann einen kurzen Moment dauern. Bitte lassen Sie diese Seite geöffnet.
		</p>
	</div>

	<div class="activation-help mt-3">
		<p class="small text-muted mb-2">
			Falls keine E-Mail angekommen ist, prüfen Sie bitte auch Ihren Spam-Ordner.
		</p>

		<form action="<?php echo $resendUrl; ?>" method="post" class="activation-resend-form">
			<button type="submit" class="btn btn-outline-secondary btn-sm">
				Bestätigungs-E-Mail erneut senden
			</button>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
	const statusText  = document.getElementById('activation-status-text');
	const subText     = document.getElementById('activation-status-subtext');
	const checkUrl    = <?php echo json_encode($checkUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
	const continueUrl = <?php echo json_encode($continueUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

	let attempts = 0;
	let redirected = false;
	const maxAttempts = 180; // 180 * 5s = 15 Minuten

	function setErrorState() {
		statusText.textContent = 'Die Aktivierung konnte gerade nicht geprüft werden.';
		subText.textContent    = 'Bitte laden Sie die Seite in einem Moment erneut oder versuchen Sie es später noch einmal.';
	}

	function checkActivation() {
		if (redirected) return;

		attempts++;

		fetch(checkUrl, {
			method: 'GET',
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			}
		})
		.then(function (response) {
			if (!response.ok) {
				throw new Error('HTTP ' + response.status);
			}
			return response.json();
		})
		.then(function (data) {
			if (data && data.activated) {
				redirected = true;
				window.location.href = continueUrl;
				return;
			}

			if (attempts < maxAttempts) {
				setTimeout(checkActivation, 5000);
			} else {
				statusText.textContent = 'Die automatische Prüfung wurde beendet.';
				subText.textContent    = 'Bitte laden Sie die Seite später erneut, nachdem Sie Ihre E-Mail bestätigt haben.';
			}
		})
		.catch(function () {
			if (attempts < maxAttempts) {
				setTimeout(checkActivation, 7000);
			} else {
				setErrorState();
			}
		});
	}

	checkActivation();
});
</script>