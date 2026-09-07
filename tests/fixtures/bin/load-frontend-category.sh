#!/usr/bin/env bash
set -Eeuo pipefail
readonly FIXTURE_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
readonly REPO_ROOT="$(cd -- "$FIXTURE_DIR/../.." && pwd)"
compose() { docker compose --project-name fdshop --file "$REPO_ROOT/compose.yaml" --env-file "$REPO_ROOT/.env" "$@"; }
fail() { printf 'Frontend fixture safety check failed: %s\n' "$1" >&2; exit 1; }
sql() { compose exec -T --env "MYSQL_PWD=${MARIADB_PASSWORD}" db mariadb --batch --skip-column-names --user="$MARIADB_USER" "$MARIADB_DATABASE" --execute "$1"; }

cd "$REPO_ROOT"
[[ -f .env ]] || fail '.env missing'
set -a; source .env; set +a
[[ "${MARIADB_DATABASE:-}" == fdshop ]] || fail 'unexpected database'
[[ "${JOOMLA_DB_PREFIX:-fd_}" =~ ^[A-Za-z0-9_]+$ ]] || fail 'unsafe Joomla DB prefix'
bash "$FIXTURE_DIR/bin/verify.sh" >/dev/null
component_id="$(sql "SELECT extension_id FROM ${JOOMLA_DB_PREFIX}extensions WHERE type='component' AND element='com_fdshop' LIMIT 1;")"
[[ "$component_id" =~ ^[0-9]+$ ]] || fail 'FDShop component missing'
max_rgt="$(sql "SELECT COALESCE(MAX(rgt),1) FROM ${JOOMLA_DB_PREFIX}menu;")"
[[ "$max_rgt" =~ ^[0-9]+$ ]] || fail 'invalid menu tree'
menu_lft=$((max_rgt + 1)); menu_rgt=$((max_rgt + 2))
rendered_sql="$(mktemp -t fdshop-frontend-fixture.XXXXXXXX.sql)"
trap '[[ -n "${rendered_sql:-}" && "$rendered_sql" == /tmp/fdshop-frontend-fixture.* ]] && rm -f -- "$rendered_sql"' EXIT
sed -e "s/__PREFIX__/${JOOMLA_DB_PREFIX}/g" -e "s/__COMPONENT_ID__/${component_id}/g" -e "s/__MENU_LFT__/${menu_lft}/g" -e "s/__MENU_RGT__/${menu_rgt}/g" "$FIXTURE_DIR/frontend-category.sql" >"$rendered_sql"
compose exec -T --env "MYSQL_PWD=${MARIADB_PASSWORD}" db mariadb --user="$MARIADB_USER" "$MARIADB_DATABASE" <"$rendered_sql"
[[ "$(sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_products p JOIN ${JOOMLA_DB_PREFIX}fdshop_product_category_map pcm ON pcm.product_id=p.id WHERE pcm.category_id=900010 AND p.is_active=1 AND p.is_deleted=0 AND (p.publish_up IS NULL OR p.publish_up<=UTC_TIMESTAMP()) AND (p.publish_down IS NULL OR p.publish_down>=UTC_TIMESTAMP());")" == 30 ]] || fail 'expected 30 visible category products'
[[ "$(sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}menu WHERE id=900900 AND alias='batterien' AND component_id=${component_id};")" == 1 ]] || fail 'frontend menu item missing'
printf 'FDShop frontend category fixtures loaded: category=900010 visible_products=30 menu=/batterien\n'
