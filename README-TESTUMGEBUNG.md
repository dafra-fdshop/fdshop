# FDShop Docker-Testumgebung – Phase 1

Diese lokale Umgebung ist ausschließlich für FDShop-Entwicklung und technische
Lebenszyklustests bestimmt. Sie verwendet keine Produktivdaten und keine
Produktivzugänge.

## Voraussetzungen

- Windows mit Docker Desktop und WSL2
- Ubuntu 24.04 mit aktivierter Docker-Desktop-Integration
- dieses Repository im Linux-Dateisystem von WSL2, nicht unter `/mnt/c`
- `bash`, `git`, `zip` und `sha256sum` in Ubuntu

## Laufzeit

- Joomla 6.1.3
- PHP 8.4
- Apache
- MariaDB 11.8
- Mailpit 1.31.0

Das Joomla-Testimage basiert unmittelbar auf dem offiziellen Image
`joomla:6.1.3-php8.4-apache`. Ergänzt werden ausschließlich die für die
freigegebenen Phase-1-Abläufe benötigten CLI-Werkzeuge.

## Ersteinrichtung

```bash
cp .env.example .env
```

Alle `CHANGE_ME`-Werte in `.env` ersetzen. `.env` wird nicht versioniert.

```bash
scripts/fdshop start
scripts/fdshop install
```

Joomla ist anschließend unter `http://localhost:8080` erreichbar, Mailpit unter
`http://localhost:8025`. Beide Ports sind ausschließlich an `127.0.0.1`
gebunden. MariaDB besitzt keinen Host-Port.

Alle Dienste kommunizieren über das isolierte Netzwerk `fdshop_internal`.
Joomla und Mailpit verwenden für ihre lokalen Host-Ports zusätzlich
`fdshop_access`; MariaDB bleibt ausschließlich im internen Netzwerk.

## Befehle

```bash
scripts/fdshop status
scripts/fdshop versions
scripts/fdshop package
scripts/fdshop install
scripts/fdshop uninstall
scripts/fdshop reinstall
scripts/fdshop sync
scripts/fdshop logs
scripts/fdshop check-logs
scripts/fdshop fixtures
scripts/fdshop fixtures-verify
scripts/fdshop test-reset
scripts/fdshop smoke
scripts/fdshop browser
scripts/fdshop test-browser
scripts/fdshop browser-readonly
scripts/fdshop test-readonly
scripts/fdshop browser-crud-manufacturers
scripts/fdshop stop
scripts/fdshop rebuild
scripts/fdshop reset
scripts/fdshop lifecycle
```

`package` erzeugt reproduzierbar `.docker/artifacts/com_fdshop.zip`. Installation
und Update laufen über Joomlas echten `extension:install`-Befehl. `uninstall`
verwendet Joomlas echten `extension:remove`-Befehl und prüft anschließend, dass
Komponenteneintrag und alle FDShop-Tabellen entfernt wurden.
Vor der CLI-Installation wird das Paket vorübergehend in Joomlas beschreibbares
`tmp`-Verzeichnis kopiert, dort als Webserver-Benutzer installiert und danach
wieder entfernt.

`sync` ist nur eine Entwicklungsbeschleunigung nach einer ordnungsgemäßen
Paketinstallation. Es synchronisiert PHP-, Template-, Formular-, SQL-, CSS- und
JavaScript-Quellen. Laufzeitbilder sowie Manifest und Installer-Script werden
nicht am Joomla-Paketweg vorbei verändert.

`fixtures` ersetzt ausschließlich FDShop-Fachdaten in der lokalen Sandbox
durch einen künstlichen, reproduzierbaren Ausgangszustand und verifiziert
Relationen, Mengen und Testdateien. `fixtures-verify` prüft ohne Datenänderung.
`test-reset` führt den Phase-1-Reset aus, installiert FDShop und lädt danach
diesen Fixture-Zustand. Die Sicherheitsgrenzen und die Abgrenzung zu späteren
Business- und Browsertests stehen in `tests/fixtures/README.md`.

`smoke` erwartet eine laufende, gesunde Sandbox mit installiertem FDShop und
geladenen Fixtures. Es verändert diesen Zustand nicht. Geprüft werden Dienste,
lokale HTTP-Endpunkte, Installation, Schema, Tabellen, Fixtures und nur die
während des aktuellen Laufs neu geschriebenen Container-/Joomla-Loganteile.
Fehlt der definierte Zustand, endet der Befehl eindeutig mit einem Fehler und
führt keinen Reset aus. Ein vollständiger Ausgangszustand entsteht weiterhin
über `test-reset`, danach kann `smoke` ausgeführt werden.

Für die ausschließlich testinterne Negativabnahme kann
`FDSHOP_SMOKE_INJECT_FAILURE=joomla-http scripts/fdshop smoke` verwendet werden.
Der Schalter verändert keine Container, Daten oder Produktdateien.

`browser` startet den schlanken, optionalen Playwright-Dienst mit Chromium im
Headless-Modus. Er erwartet wie `smoke` eine vorbereitete Sandbox und verändert
keine FDShop-Fachdaten. Der Browser erreicht Joomla ausschließlich intern über
`http://joomla`; der Dienst besitzt nur das Netzwerk `fdshop_access` und keine
veröffentlichten Ports. `test-browser` führt `test-reset`, `smoke` und danach
`browser` aus.

Playwright ist auf 1.55.0 und das Image auf
`mcr.microsoft.com/playwright:v1.55.0-noble` festgelegt. Der lokale, von Phase 1
erzeugte Testadministrator wird über die nicht versionierte `.env` übergeben.
Credentials werden weder in Testcode noch Reports ausgegeben. Ein nach echtem
Formularlogin erzeugter `storageState` liegt nur im jeweiligen Ergebnisordner.
Screenshots und Traces entstehen nur bei Fehlern, Videos sind deaktiviert.
Alle Artefakte liegen unter `.docker/test-results/playwright/` und sind über die
bereits ignorierte `.docker/`-Struktur von Git ausgeschlossen.

Für die testinterne Negativabnahme steht
`tests/browser/bin/test-controlled-failure.sh` bereit. Sie erwartet absichtlich
ein fehlendes Element und verifiziert non-zero, FAIL, Screenshot und Trace.

`browser-readonly` führt die ausschließlich lesenden Administrator-Regressionen
für Dashboard, Produkte, Kategorien, Hersteller, Bundles, Gutscheine,
Bestellungen und Konfiguration aus. Formulare werden geöffnet und Werte gelesen;
Speichern, Anwenden, Statusänderungen und andere persistierende Aktionen werden
nicht ausgeführt. `test-readonly` kombiniert Reset, Smoke, Foundation und die
Read-only-Suite.

Die Read-only-Negativabnahme liegt in
`tests/browser/bin/test-readonly-controlled-failure.sh`. Pagination wird im
Basisprofil nicht erzwungen: Die zehn Produkte liegen unter dem Seitenlimit 20.
Ein späteres separates Pagination-Fixtureprofil kann diesen Fall ergänzen, ohne
die schlanke Fixture-Basis aufzublähen.

`browser-crud-manufacturers` prüft die Herstellerverwaltung schreibend über die
echte Joomla-Oberfläche: Pflichtfeldvalidierung, Neuanlage, Apply mit ID-Erhalt,
Save & Close, Statuswechsel, Löschschutz eines verwendeten Herstellers und das
abschließende Löschen des isolierten `E2E-CRUD-MANUFACTURER-*`-Datensatzes. Für
einen garantiert sauberen Ausgangszustand vorher `test-reset` ausführen.

## Daten und Reset

Joomla, MariaDB und Mailpit verwenden ausschließlich Volumes des fest benannten
Compose-Projekts `fdshop`. `stop` erhält diese Daten. `reset` und `lifecycle`
führen ausschließlich für dieses Projekt `compose down --volumes
--remove-orphans` aus und bauen danach die Sandbox neu auf. Es werden keine
globalen Docker-Bereinigungsbefehle verwendet.

Generierte Pakete und Logsammlungen liegen unter `.docker/` und werden nicht
versioniert. Aktuelle Containerlogs bleiben mit `scripts/fdshop logs`
einsehbar; dabei werden zusätzlich Compose- und Joomla-Logs unter
`.docker/logs/<UTC-Zeitstempel>/` gesammelt.

## Verbindliche Lebenszyklusabnahme

```bash
scripts/fdshop lifecycle
```

Der Ablauf setzt die Sandbox zurück, baut und startet alle Phase-1-Dienste,
meldet die realen Versionen und prüft:

1. FDShop-Paketbau
2. Fresh Install über Joomla
3. Erweiterungseintrag, Schema-Version und vollständigen Tabellenbestand
4. Uninstall über Joomla
5. Entfernung von Erweiterungseintrag und sämtlichen FDShop-Tabellen
6. erneuten Fresh Install und dieselben Installationsprüfungen
7. Prüfung auf schwerwiegende PHP-, Exception- und SQL-Fehler
8. abschließende Logsammlung

## Abgrenzung zu Phase 2

Fixtures, Playwright, Browserautomation sowie fachliche Smoke- und
Regressionstests sind absichtlich nicht enthalten. Sie dürfen erst nach
gesonderter Freigabe ergänzt werden.
