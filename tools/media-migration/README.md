# FDShop Master Media Migration Importer

Projektinternes CLI für freigegebene PNG-Master. Der installierbare Administratorbereich `FDShop → Tools → Master-Bildimport` und dieses CLI verwenden denselben `MasterImageImportService`; beide ersetzen nicht den normalen Produkteditor-Upload.

Der Importer leitet ausschließlich `fdXXXX.png`/`FDXXXX.png` zu `FDXXXX` ab, sucht das bestehende Produkt exakt per SKU und übergibt die lokale Quelle an `ProductService::importProductImageFromLocalFile()`. Admin-Upload und Import verwenden damit denselben Media-V1-Kern für Konfiguration, Geometrie, WebP/PNG, Pfade, DB-Zuordnung, Ordering und Primary.

Aufrufe im Joomla-Container:

```bash
php /workspace/fdshop/tools/media-migration/import.php --help
php /workspace/fdshop/tools/media-migration/import.php
php /workspace/fdshop/tools/media-migration/import.php --sku=FD1234,FD1235
php /workspace/fdshop/tools/media-migration/import.php --execute --sku=FD1234,FD1235
php /workspace/fdshop/tools/media-migration/import.php --execute --all
```

Standard ist ein vollständiger, schreibfreier Dry-Run. `--execute` ohne `--sku` oder `--all` wird abgelehnt. `--master-dir` und `--report-dir` überschreiben die Standardpfade. Der Compose-Mount stellt die lokale Masterquelle ausschließlich read-only unter `/workspace/fdshop-media-masters` bereit.

Reports: `master_media_import_inventory.csv`, `master_media_import_plan.csv`, `master_media_import_results.csv`, `master_media_import_summary.txt`. Der zielinstanzbezogene, atomar ersetzte State liegt als `master_media_import_state.json` im Reportverzeichnis und wird nicht versioniert. Ein gleicher SHA mit vollständig vorhandener DB-Zuordnung und allen Derivaten ergibt `ALREADY_IMPORTED`; fremdes Media wird als `EXISTING_MEDIA_REVIEW`, geänderter Master als `MASTER_CHANGED_REVIEW`, widersprüchlicher State als `IMPORT_STATE_MISMATCH` übersprungen.

Das Werkzeug erzeugt keine Produkte, ersetzt keine fremden Bilder und besitzt keine eigene Bildpipeline. Ein späterer echter Lauf benötigt zuerst vorhandene Zielprodukte, danach vollständigen Dry-Run, Konfliktprüfung, kontrolliertes Execute, Idempotenzlauf sowie Admin-/Frontend- und Log-Stichproben.
