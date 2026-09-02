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
