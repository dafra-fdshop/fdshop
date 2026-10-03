# FDShop — Bundle-Order-Integrität, Checkout-Fix und Admin-Darstellung

## Projekt / Rahmen
- Projekt: FDShop
- Branch: `feature`
- Basis: aktueller Stand von `origin/feature`
- Kein Merge nach `main`
- Kritischer Bereich: Checkout / Bestellung / Bundle-Snapshots / Admin-Bestellung / Dokumente
- Bestehende Fachlogik und Tabellenstruktur erhalten; keine unnötige Neumodellierung.

## Ausgangslage / reproduzierter Produktionsfehler

Ein realer Test auf IONOS mit mehreren Bundles im Warenkorb erzeugte beim Checkout im Frontend:

`Unexpected token '<', "<br /> <b>"... is not valid JSON`

Der Checkout-XHR antwortete HTTP 200, aber vor dem gültigen JSON wurden PHP-Warnings ausgegeben:

`Undefined property: stdClass::$total_gross in components/com_fdshop/src/Service/CheckoutService.php on line 90`

Danach folgte gültiges Success-JSON. Die Bestellung wurde tatsächlich erzeugt, committed, der Warenkorb verarbeitet und Bestätigungsmails wurden versendet; der Browser konnte die durch HTML-Warnings verunreinigte JSON-Antwort jedoch nicht parsen und zeigte deshalb fälschlich einen Fehler statt der Bestellbestätigung.

### Bereits analysierte Ursache
`BundleService::calculate()` erzeugt und persistiert in `#__fdshop_cart_bundle_items` bereits die rabattierten Snapshotwerte:
- `unit_price_net`
- `unit_price_gross`
- `line_total_net`
- `line_total_gross`
- `bundle_discount_gross`

Die Tabelle `#__fdshop_cart_bundle_items` besitzt ausdrücklich `line_total_net` und `line_total_gross`, aber kein Feld `total_gross`.

`CheckoutService` greift beim Erzeugen von `#__fdshop_order_bundle_items` derzeit fälschlich auf `$item->total_gross` zu.

Der Fix soll die bereits berechneten/persistierten Snapshotwerte verwenden, insbesondere `line_total_net` und `line_total_gross`; keine unnötige Neuberechnung aus Brutto/globaler MwSt., sofern der vorhandene Snapshotwert fachlich führend ist.

## Wichtige Klarstellung: gleiche Produkte in mehreren Bundles

Ein Produkt darf innerhalb derselben Bestellung in mehreren unterschiedlichen Bundles vorkommen.

Beispiel:
- Bundle A enthält Produkt X
- Bundle B enthält ebenfalls Produkt X

Das ist fachlich korrekt und muss vollständig unterstützt werden.

Die bestehende Cart-Struktur unterstützt dies bereits über die Bundle-Zuordnung; keine globale Eindeutigkeit von `product_id` über verschiedene Bundles einführen. Bundlekontext darf beim Checkout, Snapshot, Admin, Dokumenten oder Lagerbedarf nicht verloren gehen.

## Zweiter Fehler: Bundles fehlen in der Admin-Bestellung

Aktuell lädt `Administrator\Model\OrderModel::getOrderItems()` nur `#__fdshop_order_items`.

Bestellte Bundles liegen dagegen in:
- `#__fdshop_order_bundles`
- `#__fdshop_order_bundle_items`

Dadurch werden Bundles im Administrator unter „Bestellpositionen“ nicht dargestellt. Eine reine Bundle-Bestellung sieht im Admin derzeit sogar wie eine leere Bestellung aus.

## Zielbild Admin-Bestellung

Normale Bestellpositionen bleiben wie bisher sichtbar.

Zusätzlich müssen bestellte Bundles als eigene, klar erkennbare Bestellblöcke dargestellt werden:
- Bundle-Name
- Bundle-Nummer
- Bundle-Gesamtpreis
- Gesamtmenge / sinnvolle Snapshot-Metadaten
- Bundle-Rabatt, sofern vorhanden
- darunter die zum konkreten Bundle gehörenden Produkte mit mindestens Menge, Produktname und SKU; sinnvolle vorhandene Preis-Snapshots dürfen dargestellt werden.

Mehrere Instanzen desselben Bundle-Typs müssen getrennt bleiben. Dasselbe Produkt darf in mehreren Bundleblöcken erscheinen.

### Verbindliche Bearbeitungsregel für bestellte Bundles

Ein Bundle ist in der Bestellung eine fachliche Einheit.

**Einzelne Produkte innerhalb eines bestellten Bundles dürfen im Administrator NICHT hinzugefügt, entfernt oder separat mengenmäßig verändert werden.**

Es gibt keine Teilbearbeitung eines Bundles.

Wenn eine Entfernung im bestehenden Bestell-Workflow vorgesehen/zulässig ist, darf ausschließlich **das komplette Bundle als Einheit** entfernt werden. Dabei müssen alle zugehörigen Bundlepositionen gemeinsam und konsistent behandelt werden.

Keine Wiederverwendung der normalen Einzelpositions-Aktionen innerhalb eines Bundleblocks.

Bestehende Regeln für Status, Bestand, Historie, Summen und Dokumente müssen bei einer vollständigen Bundleentfernung respektiert werden. Falls die vorhandene Architektur eine sichere Bundleentfernung noch nicht unterstützt, nicht improvisieren: vorhandene Order-/Stock-Services analysieren und die kleinste konsistente Erweiterung bauen.

## Integritätsprüfung aller nachgelagerten Consumer

Nicht nur die sichtbare Adminseite korrigieren.

Gezielt untersuchen, welche Order-Prozesse bislang ausschließlich `#__fdshop_order_items` konsumieren und deshalb Bundles übersehen könnten. Mindestens prüfen:
- Bestellbestätigung / Confirmation View
- Bestellbestätigungs-PDF / Dokument-Snapshot
- Packliste / Packing
- Kundenmail und interne Bestellmail
- Bestellsummen
- Lagerbedarf / Stock Allocation / Reservierung
- Statuswechsel mit Stock Actions
- Admin-Bestellung
- ggf. Storno/Entfernung
- History/Audit
- vorhandene Exporte, sofern sie Bestellpositionen ausgeben.

Nicht pauschal Bundleprodukte in `#__fdshop_order_items` duplizieren. Die vorhandene Trennung zwischen normalen Order Items und Bundle-Orderdaten bleibt erhalten.

## Checkout-Sicherheit / JSON

Ein erfolgreicher Checkout-Endpunkt muss sauberes JSON liefern. PHP-Warnings/Notices dürfen die JSON-Antwort nicht verunreinigen.

Primär die fachliche Ursache beheben; Fehlerausgaben nicht lediglich verstecken.

Idempotenz / bestehende `submission_id`-Logik erhalten. Ein Clientfehler nach erfolgreichem Commit darf nicht zu einer zweiten Bestellung führen.

## Tests — verbindlich

Gezielte Regressionstests mindestens für:

### A — nur normale Produkte
- bestehender Checkout unverändert grün.

### B — genau ein Bundle
- Checkout erfolgreich
- sauberes JSON
- korrekter Bundlekopf
- korrekte Bundlepositionen
- korrekte rabattierte Snapshotwerte
- Admin zeigt Bundle
- keine leere Bestellung.

### C — mehrere Bundles ohne Überschneidung
- alle Bundles getrennt und vollständig.

### D — mehrere Bundles mit gleichem Produkt
Beispiel:
- Bundle A: Produkt X + weitere Produkte
- Bundle B: Produkt X + andere Produkte

Prüfen:
- Checkout erfolgreich
- exakt eine Bestellung
- keine PHP-Warning/Notice
- sauberes JSON
- Produkt X existiert korrekt in beiden zugehörigen Bundle-Snapshots
- Bundlezuordnung bleibt erhalten
- Summen korrekt
- Stock Demand berücksichtigt die Gesamtmenge korrekt und ohne Doppel-/Unterzählung
- Admin zeigt beide Bundleblöcke getrennt.

### E — reine Bundle-Bestellung
- Admin darf nicht leer erscheinen.

### F — gemischte Bestellung
- normale Einzelprodukte + ein oder mehrere Bundles
- beide Arten klar und korrekt dargestellt.

### G — Bundle-Bearbeitung im Admin
- keine Einzelprodukt-Aktion innerhalb eines Bundles
- keine Mengenänderung einzelner Bundlepositionen
- sofern Bundleentfernung implementiert/vorhanden: nur komplettes Bundle, transaktional/konsistent, Summen/Stock/History korrekt.

### H — nachgelagerte Artefakte
Für mindestens reine Bundle-, gemischte und Überschneidungs-Bestellung prüfen:
- Bestellbestätigung
- PDF/Dokument
- Packliste
- Maildaten
- relevante Lager-/Statuslogik

Die Bundleinhalte müssen dort erscheinen, wo fachlich Bestell-/Packpositionen erwartet werden, ohne ihre Bundlezugehörigkeit unnötig zu verlieren.

## Performance / Architektur
- Keine N+1-Abfrage pro Bundleprodukt.
- Bundles und Bundleitems für eine Bestellung gebündelt laden.
- MVC/Service-Verantwortlichkeiten gemäß FDShop-Governance.
- Controller ohne Businesslogik/SQL.
- Snapshotdaten der Bestellung verwenden; historische Bestellung darf nicht von später geänderten Produkt-/Bundle-Stammdaten abhängen.

## Bestandsschutz
Nicht verändern, sofern zur Fehlerbehebung nicht erforderlich:
- Cart Continuation / Gast→User-Übernahme
- Joomla Login/Registrierung
- Paymentlogik außerhalb notwendiger Regression
- Couponlogik
- Favoriten/Warenkorbmodule
- Bundle Builder UX
- Produktdetailansicht
- normale Einzelprodukt-Bestellbearbeitung.

## Freiheiten / STOP
Codex darf innerhalb dieses Scopes betroffene Services, Models, Views/Templates, Dokument-/Packing-Code und gezielte Tests selbstständig analysieren und die kleinste architektonisch saubere Lösung umsetzen.

Behebbare Fehler, notwendige Testfixture-Anpassungen und klar ableitbare Folgekorrekturen sind kein STOP-Grund.

STOP nur bei echter fachlicher Mehrdeutigkeit mit Risiko für Bestands-/Zahlungs-/Bestelldaten.

## Abschluss
- Paket/Schema nur nach Projektstandard erhöhen; Schema nur wenn echte DB-Änderung erforderlich.
- Dokumentation aktualisieren, falls Architektur/Fachverhalten betroffen.
- Auftrag nach `Aufträge/erledigt`.
- gezielte Tests + Syntax + `git diff --check` + Paketbau/Installation + Smoke + Logprüfung.
- Kein `test-full`, sofern nicht zwingend erforderlich.
- keine Secrets.
- Push nach `origin/feature`.
- kein Merge nach `main`.
- Windows/WSL sauber und synchron.

## Abschlussbericht
Kurz, aber vollständig berichten:
1. Root Cause Checkout
2. konkrete Korrektur der Bundle-Snapshots
3. Admin-Darstellung
4. Bundle-Bearbeitungsregel
5. geprüfte nachgelagerte Consumer und ggf. dortige Fixes
6. Testszenarien inkl. überschneidendem Produkt
7. Paket-/Schemaversion
8. geänderte Dateien
9. Commit-SHA / Pushstatus

Und für Codex: Diesmal ist das Bundle wirklich eine Einheit. Bitte keine chirurgische Entfernung von „Airpower 2“ aus einem bereits bestellten Bundle – wir verkaufen Feuerwerk und keine Organtransplantationen. 😎🤣
