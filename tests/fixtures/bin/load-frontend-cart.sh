#!/usr/bin/env bash
set -Eeuo pipefail
readonly FIXTURE_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
readonly REPO_ROOT="$(cd -- "$FIXTURE_DIR/../.." && pwd)"
compose() { docker compose --project-name fdshop --file "$REPO_ROOT/compose.yaml" --env-file "$REPO_ROOT/.env" "$@"; }
fail() { printf 'Frontend cart fixture safety check failed: %s\n' "$1" >&2; exit 1; }
sql() { compose exec -T --env "MYSQL_PWD=${MARIADB_PASSWORD}" db mariadb --batch --skip-column-names --user="$MARIADB_USER" "$MARIADB_DATABASE" --execute "$1"; }

cd "$REPO_ROOT"
[[ -f .env ]] || fail '.env missing'
set -a; source .env; set +a
[[ "${MARIADB_DATABASE:-}" == fdshop ]] || fail 'unexpected database'
[[ "${JOOMLA_DB_PREFIX:-fd_}" =~ ^[A-Za-z0-9_]+$ ]] || fail 'unsafe Joomla DB prefix'
bash "$FIXTURE_DIR/bin/verify.sh" >/dev/null
component_id="$(sql "SELECT extension_id FROM ${JOOMLA_DB_PREFIX}extensions WHERE type='component' AND element='com_fdshop' LIMIT 1;")"
user_id="$(sql "SELECT id FROM ${JOOMLA_DB_PREFIX}users WHERE username='${JOOMLA_ADMIN_USERNAME}' LIMIT 1;")"
[[ "$component_id" =~ ^[0-9]+$ ]] || fail 'FDShop component missing'
[[ "$user_id" =~ ^[0-9]+$ ]] || fail 'Joomla test user missing'
max_rgt="$(sql "SELECT COALESCE(MAX(rgt),1) FROM ${JOOMLA_DB_PREFIX}menu;")"
menu_lft=$((max_rgt + 1)); menu_rgt=$((max_rgt + 2))
rendered_sql="$(mktemp -t fdshop-cart-fixture.XXXXXXXX.sql)"
trap '[[ -n "${rendered_sql:-}" && "$rendered_sql" == /tmp/fdshop-cart-fixture.* ]] && rm -f -- "$rendered_sql"' EXIT
sed -e "s/__PREFIX__/${JOOMLA_DB_PREFIX}/g" -e "s/__COMPONENT_ID__/${component_id}/g" -e "s/__JOOMLA_USER_ID__/${user_id}/g" -e "s/__MENU_LFT__/${menu_lft}/g" -e "s/__MENU_RGT__/${menu_rgt}/g" "$FIXTURE_DIR/frontend-cart.sql" >"$rendered_sql"
compose exec -T --env "MYSQL_PWD=${MARIADB_PASSWORD}" db mariadb --user="$MARIADB_USER" "$MARIADB_DATABASE" <"$rendered_sql"
[[ "$(sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_cart WHERE user_id=${user_id};")" == 3 ]] || fail 'expected three owned cart items'
[[ "$(sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}fdshop_cart WHERE user_id<>${user_id};")" == 1 ]] || fail 'expected isolated foreign cart item'
[[ "$(sql "SELECT COUNT(*) FROM ${JOOMLA_DB_PREFIX}menu WHERE id=900901 AND alias='warenkorb';")" == 1 ]] || fail 'cart menu item missing'
printf 'FDShop frontend cart fixtures loaded: user=%s owned_items=3 menu=/warenkorb\n' "$user_id"
