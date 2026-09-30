# FDShop – Verbindliche Cutover-Notiz: Testbestellungen und Testrechnungen

Stand: 30.09.2026

Diese Notiz hält eine verbindliche Projektentscheidung für den späteren Go-live-/Cutover fest.

## Ausgangslage

Bis zum produktiven Go-live entstehen in FDShop ausschließlich Testbestellungen und daraus ggf. erzeugte Testartefakte.

Es wird vor dem Cutover keine abgeschlossene produktive FDShop-Bestellung geben, die erhalten werden muss.

## Verbindliche Entscheidung

Beim späteren Löschen/Bereinigen der FDShop-Testbestellungen werden die zu diesen Testbestellungen gehörenden Testdaten vollständig mit entfernt.

Das umfasst ausdrücklich auch bereits erzeugte oder später stornierte Testrechnungen.

Eine stornierte Testrechnung ist im Testbetrieb kein aufzubewahrender Produktivbeleg.

Beim Testdaten-Cleanup sind deshalb – jeweils ausschließlich bezogen auf Testbestellungen – auch abhängige Testartefakte zu berücksichtigen, insbesondere:

- Testbestellungen
- Bestellpositionen / Bundle-Positionen
- Bestellhistorien / Statushistorien
- Bestands-/Order-Allokationen und sonstige orderbezogene Testzuordnungen
- Payment Sessions / Transactions / Reservations, soweit der Testorder zuordenbar und nach bestehender Cleanup-Architektur sicher entfernbar
- Order Documents
- Rechnungs-PDFs / Rechnungsdokumente
- stornierte Testrechnungen und deren Dokumentversionen
- testbezogene Versand-/Shipment-Daten
- sonstige ausschließlich zu diesen Testbestellungen gehörende Artefakte

Ziel des finalen Cutovers ist ein sauberer produktiver Ausgangsstand ohne zurückbleibende Testbestellungen oder Testrechnungen.

## Rechnungsnummern

Die während der Entwicklungs-/Testphase vergebenen Rechnungsnummern bestimmen NICHT den endgültigen produktiven Rechnungsnummernstand.

Beim finalen Cutover wird nach Schließung des alten Live-Shops der reale letzte Rechnungsstand des Altsystems festgestellt/migriert und die produktive FDShop-Rechnungsnummernfortsetzung passend initialisiert.

Testrechnungsnummern dürfen daher zusammen mit den Testdaten bereinigt werden.

## Abgrenzung

Diese Regel gilt ausschließlich für den Vor-Go-live-Testbestand.

Sobald FDShop produktiv ist, dürfen echte Rechnungen – einschließlich stornierter echter Rechnungen – NICHT nach dieser Test-Cleanup-Regel gelöscht werden. Für produktive Belege gelten die fachlichen Aufbewahrungs-/Storno-Regeln des Rechnungsprozesses.

## Umsetzung später

Der eigentliche Cleanup/Cutover wird separat beauftragt und soll nicht ad hoc per unvollständigen Einzel-DELETEs erfolgen.

Vor Ausführung muss die dann aktuelle Tabellen-/FK-/Dokumentstruktur analysiert werden, damit ausschließlich Testdaten vollständig und konsistent entfernt werden.
