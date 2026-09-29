# PayPal Checkout V1

FDShop nutzt für PayPal einen zweistufigen, serverseitig kontrollierten Ablauf. Vor dem Capture existiert keine FDShop-Bestellung. Eine providerneutrale Payment-Session enthält den unveränderlichen Checkout-Kontext, Betrag/Währung und Ablaufzeit; zugehörige Reservierungen belegen den physischen Bedarf. Providerantworten werden getrennt als Transaktionen gespeichert.

## Ablauf und Zustände

`reserved` → `payment_in_progress` → `capturing` → `captured` → `completed`. Abgelaufene oder endgültig fehlgeschlagene Vorgänge werden genau einmal freigegeben. `capturing` und `captured` werden vom Cleaner niemals freigegeben. Ein erfolgreicher Capture wird vor der lokalen Orderfinalisierung dauerhaft gespeichert. Schlägt diese danach fehl, wiederholt ein späterer Browser- oder Webhook-Aufruf ausschließlich die Finalisierung – niemals den Capture.

Der bestehende `CheckoutService` bleibt alleiniger Finalizer für Order, Snapshots, Lagerzuordnung, Gutscheinverbrauch, History, Warenkorblöschung, Mail und Dokumente. Bei PayPal übernimmt die Paid-Order die temporäre Reservierung ohne Freigabe-/Neureservierungslücke. `submission_id`, Session-/Provider-/Capture-UNIQUE-Constraints und Zeilensperren sichern die Idempotenz.

## Browser und Webhook

Der Warenkorb lädt ausschließlich bei einer Zahlart mit `paypal_enabled=1` das offizielle PayPal Web SDK v6. Create Order und Capture laufen serverseitig. Browserpreise werden ignoriert. Der Webhook-Endpunkt lautet:

Redirect- und Payment-Handler-Rückläufe werden nach dem erneuten Seitenaufbau mit `paymentSession.hasReturned()` erkannt und durch `paymentSession.resume()` fortgesetzt. Erst dadurch löst das SDK den registrierten `onApprove`-Callback aus. Der dafür benötigte interne Payment-Session-Bezug wird ausschließlich im tabgebundenen `sessionStorage` bis Approval, Cancel, Fehler oder Ablauf gehalten.

### Erforderlicher Joomla-HTTP-Header

Für die Kommunikation zwischen FDShop und dem PayPal-Popup muss der effektive Response-Header der Warenkorbseite `Cross-Origin-Opener-Policy: same-origin-allow-popups` lauten. Joomlas Standardwert `same-origin` trennt das fremde PayPal-Popup vom öffnenden Browsing Context und verhindert den Buyer-Flow. Die Einstellung erfolgt im Joomla-Plugin **System – HTTP Headers**; FDShop verändert weder Joomla-Core noch das Plugin automatisch. Nach Installation beziehungsweise Serverumzug muss der tatsächlich ausgelieferte Header im Browser-Netzwerk oder per HTTP-HEAD geprüft werden. Mehrfach gesetzte, widersprüchliche COOP-Header sind zu vermeiden.

`https://IHRE-DOMAIN/index.php?option=com_fdshop&task=payment.webhook&format=json`

Im PayPal-Dashboard muss Daniel später für die jeweilige Live-/Sandbox-App einen Webhook mit mindestens `PAYMENT.CAPTURE.COMPLETED` anlegen und dessen Webhook-ID als Environment-Variable hinterlegen. Ohne Webhook-ID lehnt FDShop jede Webhookmutation ab. Signaturen werden über PayPals offiziellen `verify-webhook-signature`-Endpunkt geprüft; Replays laufen durch denselben idempotenten Finalizer.

## Credentials in Docker und auf IONOS

FDShop löst PayPal-Zugangsdaten zentral in dieser Reihenfolge auf:

1. nichtleere Environment-Variable für den aktiven Modus,
2. externer Secret-File-Eintrag,
3. nicht konfiguriert.

Ein Environment-Wert hat immer Vorrang und wird niemals durch die Datei überschrieben. Die WSL2-/Docker-Testumgebung verwendet weiterhin die Variablen aus `.env.example`. Optional kann `FDSHOP_SECRET_FILE` einen absoluten, serverseitigen Dateipfad vorgeben.

Auf IONOS liegt die Datei außerhalb des Joomla-DocumentRoots. Ohne explizites `FDSHOP_SECRET_FILE` wird sie installationsunabhängig relativ zum aktuellen Joomla-Root als Geschwisterpfad aufgelöst:

`dirname(JPATH_ROOT)/fdshop-data/secrets/fdshop-secrets.php`

Damit wird insbesondere kein Ordnername wie `Joomla6` hardcodiert. Bei einem Umzug muss entweder `fdshop-data` mit derselben Geschwisterstruktur übernommen oder `FDSHOP_SECRET_FILE` serverseitig auf den neuen absoluten Pfad gesetzt werden.

Die externe, nicht paketierte und nicht versionierte Datei muss ausschließlich dieses Datenarray zurückgeben:

```php
<?php
return [
    'paypal' => [
        'mode' => 'sandbox', // oder 'live'
        'sandbox' => [
            'client_id' => 'SANDBOX_CLIENT_ID_HIER',
            'client_secret' => 'SANDBOX_CLIENT_SECRET_HIER',
            'webhook_id' => 'SANDBOX_WEBHOOK_ID_HIER',
        ],
        'live' => [
            'client_id' => 'LIVE_CLIENT_ID_HIER',
            'client_secret' => 'LIVE_CLIENT_SECRET_HIER',
            'webhook_id' => 'LIVE_WEBHOOK_ID_HIER',
        ],
    ],
];
```

Das Format wird defensiv validiert. Fehlende, unlesbare, unvollständige oder ungültige Dateien gelten ohne Frontend-Warnung als nicht beziehungsweise nur teilweise konfiguriert. Die Datei erzeugt bei direkter Ausführung keine Ausgabe. Client Secret und Webhook-ID bleiben ausschließlich serverseitig; Geheimnisse gehören niemals in Datenbank, Paket, Repository, Logs, Screenshots oder Traces. Der Admin zeigt weiterhin nur Ja/Nein-Zustände. Die Reservierungsdauer wird im FDShop-Admin zwischen 5 und 30 Minuten konfiguriert (Standard: 10).

Die manuelle Realabnahme erfolgt auf der online bei IONOS installierten Paketversion. Sie ist von der lokalen WSL2-/Docker-Automatiktestumgebung zu unterscheiden.

## Cleanup und Betrieb

Das mitgelieferte Joomla-Task-Plugin `FDShop: Abgelaufene Zahlungsreservierungen bereinigen` sollte im Joomla Scheduler regelmäßig (empfohlen: jede Minute) ausgeführt werden. Zusätzlich bereinigt ein sicherer Payment-Start opportunistisch abgelaufene Sessions. Der Browser-Countdown ist nur Anzeige und niemals Cleanup-Wahrheit.

## Fehler und Retry

Cancel, Popup-Schließen, PENDING und Fehler vor Capture lassen Warenkorb und – bis Ablauf – Reservierung bestehen. Endgültige Fehler/Expiry geben sie kontrolliert frei. Betrag oder Währung müssen exakt dem serverseitigen Erwartungswert entsprechen. Capture-Erfolg mit lokaler Finalisierungsstörung bleibt als `captured` diagnostizierbar und retryfähig.

## Tests

- `tests/paypal/payment-service-regression.php`: Reservierung, Capture, Finalizerfehler/Retry, Idempotenz, Webhook-Replay, Expiry und Race-Schutz.
- `tests/paypal/sandbox-smoke.php`: echte Sandbox-Authentifizierung und Create Order ohne Capture.
- `tests/browser/tests/paypal-checkout.spec.js`: v6-Browserintegration und Cancel/Cart-Erhalt.
- `tests/paypal/migration.sh`: Upgrade `0.0.41 → 0.0.42`.

Ein öffentlich erreichbarer, im PayPal-Dashboard registrierter Webhook kann lokal nicht vollständig end-to-end geprüft werden und bleibt bis zur Einrichtung durch Daniel/Caesar offen.
