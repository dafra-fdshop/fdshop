# PayPal Checkout V1

FDShop nutzt für PayPal einen zweistufigen, serverseitig kontrollierten Ablauf. Vor dem Capture existiert keine FDShop-Bestellung. Eine providerneutrale Payment-Session enthält den unveränderlichen Checkout-Kontext, Betrag/Währung und Ablaufzeit; zugehörige Reservierungen belegen den physischen Bedarf. Providerantworten werden getrennt als Transaktionen gespeichert.

## Ablauf und Zustände

`reserved` → `payment_in_progress` → `capturing` → `captured` → `completed`. Abgelaufene oder endgültig fehlgeschlagene Vorgänge werden genau einmal freigegeben. `capturing` und `captured` werden vom Cleaner niemals freigegeben. Ein erfolgreicher Capture wird vor der lokalen Orderfinalisierung dauerhaft gespeichert. Schlägt diese danach fehl, wiederholt ein späterer Browser- oder Webhook-Aufruf ausschließlich die Finalisierung – niemals den Capture.

Der bestehende `CheckoutService` bleibt alleiniger Finalizer für Order, Snapshots, Lagerzuordnung, Gutscheinverbrauch, History, Warenkorblöschung, Mail und Dokumente. Bei PayPal übernimmt die Paid-Order die temporäre Reservierung ohne Freigabe-/Neureservierungslücke. `submission_id`, Session-/Provider-/Capture-UNIQUE-Constraints und Zeilensperren sichern die Idempotenz.

## Browser und Webhook

Der Warenkorb lädt ausschließlich bei einer Zahlart mit `paypal_enabled=1` das offizielle PayPal Web SDK v6. Create Order und Capture laufen serverseitig. Browserpreise werden ignoriert. Der Webhook-Endpunkt lautet:

`https://IHRE-DOMAIN/index.php?option=com_fdshop&task=payment.webhook&format=json`

Im PayPal-Dashboard muss Daniel später für die jeweilige Live-/Sandbox-App einen Webhook mit mindestens `PAYMENT.CAPTURE.COMPLETED` anlegen und dessen Webhook-ID als Environment-Variable hinterlegen. Ohne Webhook-ID lehnt FDShop jede Webhookmutation ab. Signaturen werden über PayPals offiziellen `verify-webhook-signature`-Endpunkt geprüft; Replays laufen durch denselben idempotenten Finalizer.

## Environment

Siehe `.env.example`. Client Secret und Webhook-ID sind ausschließlich serverseitige Environment-Werte. Sandbox und Live sind strikt getrennt. Geheimnisse gehören niemals in Datenbank, Repository, Logs, Screenshots oder Traces. Die Reservierungsdauer wird im FDShop-Admin zwischen 5 und 30 Minuten konfiguriert (Standard: 10).

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
