# Produktinteraktionen V1

- Die Verfügbarkeits-Watchlist steht registrierten Joomla-Benutzern für ausverkaufte Produkte zur Verfügung. Gäste werden zur Anmeldung oder Registrierung geführt.
- Bestandsänderungen versenden niemals automatisch E-Mails. Nur die bestätigte Adminaktion im Produkt versendet Benachrichtigungen.
- Empfänger werden einzeln verarbeitet. Erfolgreiche Zustellungen werden sofort als `notified` markiert; fehlgeschlagene Einträge bleiben `active` und können ohne Doppelversand erneut versucht werden.
- Aktive Vormerkungen erscheinen aggregiert im Admin-Dashboard, detailliert im Produkt und eigentümergebunden unter „Mein Konto“.
- Eine Vormerkung verleiht keine F3-Berechtigung; die zentrale Buyer-Eligibility-Prüfung bleibt beim späteren Kauf unverändert aktiv.
- Produktfragen sind einmalige Kontaktanfragen für Gäste und angemeldete Benutzer, kein Abonnement und kein Double-Opt-in-Workflow.
- Produktfragen verwenden das global konfigurierte Joomla-Standard-CAPTCHA. Ohne konfiguriertes CAPTCHA greift Joomlas eigener leerer Fallback; Honeypot, CSRF-Prüfung und ein sitzungsbasiertes Rate-Limit bleiben aktiv.
- Produktdaten, SKU und URL werden serverseitig geladen. Der Fragetext wird nur per E-Mail an die bestehende Firmenadresse gesendet und nicht in FDShop gespeichert.
- Der optionale Systemhinweis direkt beim Speichern einer Lagerauffüllung wurde in V1 bewusst nicht an die Bestandsmutation gekoppelt. Die offene Anzahl ist stattdessen im Dashboard und im Produkt sichtbar; der Versand bleibt damit eindeutig eine separate Adminaktion.
