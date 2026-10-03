# Bestellte Bundles

Bestellte Bundles werden getrennt von normalen Bestellpositionen als unveränderliche Snapshots in `#__fdshop_order_bundles` und `#__fdshop_order_bundle_items` gespeichert. Die beim Warenkorb berechneten Netto-/Brutto-Zeilenwerte sind dabei führend; ein Checkout berechnet historische Bundlepositionen nicht erneut aus aktuellen Produktdaten.

Ein Bundle bleibt in Admin, Bestätigung, Dokumenten und Packliste als eigene Einheit erkennbar. Seine Kinder sind im Administrator schreibgeschützt. V1 bietet keine Bundleentfernung im Bestellentwurf an, weil der bestehende Workflow nur normale Einzelpositionen atomar bearbeitet. Normale Positionen bleiben unverändert editierbar.

Dasselbe Produkt darf in mehreren Bundles derselben Bestellung vorkommen. Der Bundlekontext bleibt pro `order_bundle_id` erhalten; nur der physische Lagerbedarf wird produktweise über alle normalen und Bundlepositionen summiert.

Die `submission_id` bleibt der Idempotenzschlüssel des Checkouts. Eine Wiederholung nach bereits erfolgreichem Commit liefert dieselbe Bestellung zurück und erzeugt keine zweite.
