#!/usr/bin/env bash
set -Eeuo pipefail
readonly FIXTURE_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
readonly REPO_ROOT="$(cd -- "$FIXTURE_DIR/../.." && pwd)"
readonly COMPOSE_PROJECT=fdshop
compose() { docker compose --project-name fdshop --file "$REPO_ROOT/compose.yaml" --env-file "$REPO_ROOT/.env" "$@"; }
fail() { printf 'Fixture verification failed: %s\n' "$1" >&2; exit 1; }
sql() { compose exec -T --env "MYSQL_PWD=${MARIADB_PASSWORD}" db mariadb --batch --skip-column-names --user="$MARIADB_USER" "$MARIADB_DATABASE" --execute "$1"; }
expected() { python3 -c 'import json,sys; d=json.load(open(sys.argv[1])); print(d["counts"][sys.argv[2]])' "$FIXTURE_DIR/expected.json" "$1"; }
assert_count() { local table="$1"; local actual; actual="$(sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_${table};")"; [[ "$actual" == "$(expected "$table")" ]] || fail "$table count $actual"; }
assert_sql() { [[ "$(sql "$1")" == "$2" ]] || fail "$3"; }

cd "$REPO_ROOT"; [[ -f .env ]] || fail '.env missing'; set -a; source .env; set +a
[[ "${MARIADB_DATABASE:-}" == fdshop ]] || fail 'unexpected database'
[[ "$(compose exec -T joomla printenv JOOMLA_DB_HOST)" == db:3306 ]] || fail 'external database host'
[[ "$(compose ps --format json db | grep -o '"Project":"[^"]*"' | head -1 | cut -d'"' -f4)" == fdshop ]] || fail 'unexpected Compose project'
for table in manufacturers categories buyer_groups products products_details product_category_map product_buyer_group_map user_buyer_group_map media bundles bundle_items bundle_discount_rules coupons coupon_user_map coupon_buyer_group_map coupon_product_map coupon_category_map shipments payment_methods order_statuses orders order_items order_bundles order_bundle_items order_history order_status_history; do assert_count "$table"; done
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_price_calc_rules;" 0 'price calculation rules not reset'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_product_prices;" 0 'product prices not reset'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_product_prices_research;" 0 'price research not reset'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_coupon_usage;" 0 'coupon usage not reset'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_cart;" 0 'cart not reset'

assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_products_details WHERE sku LIKE 'E2E-PROD-%';" 10 'stable product SKUs'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_products WHERE is_active=0 AND is_deleted=0;" 1 'inactive product'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_products WHERE is_deleted=1;" 1 'trashed product'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_products WHERE discount_active=1 AND discount_price>0;" 1 'active discount product'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_products_details WHERE stock_quantity=0 AND is_in_stock=0;" 1 'sold-out product'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_products_details WHERE stock_quantity>0 AND stock_quantity<=low_stock;" 1 'low-stock product'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_product_category_map WHERE product_id=900108;" 2 'multi-category relation'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_product_buyer_group_map WHERE product_id=900109;" 3 'buyer-group relations'
assert_sql "SELECT CONCAT(parent_id,':',level) FROM ${JOOMLA_DB_PREFIX}fdshop_categories WHERE alias='e2e-child';" '900010:2' 'category hierarchy'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_media WHERE product_id=900103 AND is_primary=1;" 1 'media relation'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_bundle_items WHERE bundle_id=900400;" 2 'bundle positions'
assert_sql "SELECT GROUP_CONCAT(CONCAT(min_quantity,':',discount_percent) ORDER BY ordering SEPARATOR ',') FROM ${JOOMLA_DB_PREFIX}fdshop_bundle_discount_rules WHERE bundle_id=900400;" '2.000:5.0000,4.000:10.0000' 'bundle discount rules'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_coupons WHERE coupon_code LIKE 'E2E-%';" 8 'coupon keys'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_coupons WHERE discount_type='percent';" 5 'percent coupons'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_coupons WHERE discount_type='fixed';" 3 'fixed coupons'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_coupons WHERE valid_to<'2026-01-01';" 1 'expired coupon'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_coupons WHERE valid_from>'2030-01-01';" 1 'future coupon'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_coupons WHERE minimum_order_total=100;" 1 'minimum order total'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_coupon_product_map WHERE coupon_id=900505 AND product_id=900100;" 1 'coupon product mapping'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_coupon_category_map WHERE coupon_id=900506 AND category_id=900010;" 1 'coupon category mapping'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_coupon_buyer_group_map WHERE coupon_id=900507 AND buyer_group_id=900021;" 1 'coupon buyer-group mapping'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_coupon_user_map WHERE coupon_id=900507;" 1 'coupon user mapping'
assert_sql "SELECT GROUP_CONCAT(published ORDER BY ordering SEPARATOR ',') FROM ${JOOMLA_DB_PREFIX}fdshop_shipments;" '1,0' 'shipment states/order'
assert_sql "SELECT GROUP_CONCAT(published ORDER BY ordering SEPARATOR ',') FROM ${JOOMLA_DB_PREFIX}fdshop_payment_methods;" '1,0' 'payment states/order'
assert_sql "SELECT GROUP_CONCAT(is_active ORDER BY ordering SEPARATOR ',') FROM ${JOOMLA_DB_PREFIX}fdshop_order_statuses;" '1,1,0' 'order status states/order'
assert_sql "SELECT GROUP_CONCAT(CONCAT(order_number,':',has_bundle) ORDER BY id SEPARATOR ',') FROM ${JOOMLA_DB_PREFIX}fdshop_orders;" 'E2E-ORDER-NORMAL:0,E2E-ORDER-BUNDLE:1' 'order snapshots'
assert_sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_order_bundle_items WHERE order_bundle_id=900820;" 2 'order bundle snapshots'
compose exec -T joomla test -f /var/www/html/images/FDShop/products/e2e-fixture-product.svg || fail 'synthetic image missing'
file_hash="$(compose exec -T joomla sha256sum /var/www/html/images/FDShop/products/e2e-fixture-product.svg | cut -d' ' -f1)"
source_hash="$(sha256sum "$FIXTURE_DIR/files/e2e-fixture-product.svg" | cut -d' ' -f1)"
[[ "$file_hash" == "$source_hash" ]] || fail 'synthetic image hash mismatch'
fingerprint="$(sql "SELECT SHA2(GROUP_CONCAT(v ORDER BY v SEPARATOR '|'),256) FROM (SELECT CONCAT('p:',id,':',alias,':',sale_price,':',discount_price,':',discount_active,':',is_active,':',is_deleted) v FROM ${JOOMLA_DB_PREFIX}fdshop_products UNION ALL SELECT CONCAT('d:',id,':',sku,':',stock_quantity,':',low_stock,':',is_in_stock) FROM ${JOOMLA_DB_PREFIX}fdshop_products_details UNION ALL SELECT CONCAT('c:',id,':',coupon_code,':',discount_type,':',discount_value,':',minimum_order_total) FROM ${JOOMLA_DB_PREFIX}fdshop_coupons UNION ALL SELECT CONCAT('o:',id,':',order_number,':',grand_total,':',has_bundle) FROM ${JOOMLA_DB_PREFIX}fdshop_orders) fixture_state;")"
[[ "$fingerprint" =~ ^[0-9A-Fa-f]{64}$ ]] || fail 'state fingerprint missing'
printf 'FDShop fixtures verified: fingerprint=%s file=%s\n' "$fingerprint" "$file_hash"
