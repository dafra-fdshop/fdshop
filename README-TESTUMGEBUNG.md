# FDShop Docker-Testumgebung – Phase 2

Zentrale lokale Entwicklungs- und Abnahmeumgebung mit ausschließlich künstlichen Fixtures. Keine Produktivdaten, Produktivzugänge oder externe Produktivdatenbank.

## Voraussetzungen und Start

- Windows, Docker Desktop und WSL2 mit Ubuntu 24.04
- Repository im WSL-Dateisystem: `/home/daniel/projects/fdshop` (nicht `/mnt/c`)
- Joomla 6.1.3, PHP 8.4, MariaDB 11.8, Mailpit 1.31.0

```bash
cd /home/daniel/projects/fdshop
cp .env.example .env
# CHANGE_ME-Werte lokal ersetzen; .env wird nicht versioniert
scripts/fdshop test-reset
```

Joomla und Mailpit sind nur an `127.0.0.1` gebunden; MariaDB hat keinen Host-Port. Resets betreffen ausschließlich das Compose-Projekt `fdshop`.

## QUICK, STANDARD und FULL

QUICK ist ein nicht destruktiver Gesundheitscheck einer vorbereiteten Sandbox:

```bash
scripts/fdshop test-quick
```

STANDARD erzeugt einen definierten Ausgang, führt Smoke, Browser-Foundation, Read-only und genau eine CRUD-Suite aus und stellt abschließend Fixtures und Logs sicher:

```bash
scripts/fdshop test-standard manufacturers
scripts/fdshop test-standard categories
scripts/fdshop test-standard configuration
scripts/fdshop test-standard products
scripts/fdshop test-standard coupons
scripts/fdshop test-standard bundles
scripts/fdshop test-standard orders
```

Unbekannte oder fehlende Bereiche enden non-zero und nennen alle gültigen Werte.

FULL führt den gesamten Phase-2-Bestand aus. Jede CRUD-Suite erhält einen frischen Fixture-Ausgang, weil insbesondere Configuration Referenzdaten und Orders historische Soft-Remove-/History-Daten hinterlassen. Danach folgen Fixtureintegrität, Smoke, Log- und Paketinhaltsprüfung sowie Lifecycle.

```bash
scripts/fdshop test-full
```

FULL ist erforderlich bei Installer-, Schema-/Update-SQL-, breit wirkenden Service-/Businesslogik-, zentralen Model-, Paketbau- oder Testinfrastrukturänderungen, vor größeren Main-Merges und für Release-/Gesamtabnahmen. Bei isolierten CSS-, Text- oder Templatekorrekturen reichen üblicherweise QUICK plus passende STANDARD-Suite.

## Einzelbefehle

`scripts/fdshop` bleibt der einzige Orchestrierungseinstieg.

- Betrieb: `start`, `stop`, `rebuild`, `reset`, `status`, `versions`
- Paket: `package`, `install`, `uninstall`, `reinstall`, `sync`, `verify-install`, `verify-uninstall`, `lifecycle`
- Diagnose: `logs`, `check-logs`
- Fixtures: `fixtures`, `fixtures-verify`, `test-reset`
- Basis: `smoke`, `browser`, `test-browser`, `browser-readonly`, `test-readonly`
- CRUD: `browser-crud-manufacturers`, `browser-crud-categories`, `browser-crud-configuration`, `browser-crud-products`, `browser-crud-coupons`, `browser-crud-bundles`, `browser-crud-orders`
- Gesamtwege: `test-quick`, `test-standard AREA`, `test-full`

Playwright 1.55.0 nutzt Chromium headless. Credentials kommen nur aus `.env`. Screenshots und Traces entstehen bei Fehlern, Videos nicht. Artefakte, Logs und Pakete unter `.docker/` sind nicht versioniert. Der stale-freie Paketbau enthält keine Tests, Node-/Playwright-Dateien, Artefakte oder Altdateien; Installation und Uninstall laufen über Joomla CLI.

## Empfohlener Ablauf

```text
Auftrag analysieren → unverbindliche Zeitschätzung → Implementierung → QUICK
→ relevante STANDARD-Suite → eigene in-scope Fehler korrigieren und wiederholen
→ bei risikoreicher/größerer Änderung FULL → erst bei Grün Commit
→ Push nach feature → menschliche Abnahme
```

Neue Businessregeln, Architektur-, Datenmodell- und Governance-Entscheidungen bleiben bei der Projektleitung. GitHub Actions/CI und eine PHP-8.5-Zweitumgebung sind nicht Teil von Phase 2.

## Bewusste Grenzen (SKIPPED, nicht PASS)

- Pagination mit Basis-Fixtures
- Produktbild löschen
- Versandart löschen
- Zahlungsart löschen
- Bestellstatus neu anlegen (keine UI)
- Bundle Trash/Restore
- Bestell-Bundle-Nummeranzeige
- Bundle-Unterpositionen im Bestelldetail
- Bundle-Item-Editing in Bestellungen
- Bestell Trash/Restore (mit zwei benötigten Snapshots nicht sicher isoliert)
- Frontend, Warenkorb, Checkout und operative Bestellungserzeugung

Im Gutscheinmodell fehlen weiterhin `maximum_discount_amount`, `coupon_type` und ein Allow/Exclude-Zuordnungsmodus. Technisch implementiert sind unter anderem `valid_to`, `usage_limit_per_user` sowie `percent` und `fixed`; abweichende Fachquellenbegriffe werden nicht automatisch gleichgesetzt.

STANDARD und FULL brechen beim ersten Fehler non-zero mit dem betroffenen Abschnitt ab. Nach CRUD-Fehlern wird soweit sicher möglich der Fixturezustand wiederhergestellt; Screenshots und Traces bleiben erhalten. Es gibt keine globale Docker-Bereinigung.
