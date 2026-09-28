#!/usr/bin/env bash
set -Eeuo pipefail
readonly SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
readonly REPO_ROOT="$(cd -- "$SCRIPT_DIR/../.." && pwd)"
cd "$REPO_ROOT"
[[ -f .env ]] || { echo 'Sandbox .env fehlt.' >&2; exit 1; }
set -a; source .env; set +a
[[ "${MARIADB_DATABASE:-}" == fdshop ]] || { echo 'Migrationstest verweigert eine fremde Datenbank.' >&2; exit 1; }
[[ "${JOOMLA_DB_PREFIX:-fd_}" =~ ^[A-Za-z0-9_]+$ ]] || { echo 'Unsicheres Tabellenpräfix.' >&2; exit 1; }
compose(){ docker compose --project-name fdshop --file compose.yaml --env-file .env "$@"; }
sql(){ compose exec -T --env "MYSQL_PWD=${MARIADB_PASSWORD}" db mariadb --batch --skip-column-names --user="${MARIADB_USER}" "${MARIADB_DATABASE}" --execute "$1"; }
[[ "$(compose ps --format json db | grep -o '"Project":"[^"]*"' | head -1)" == *'"Project":"fdshop"'* ]] || { echo 'Falsches Compose-Projekt.' >&2; exit 1; }
extension_id="$(sql "SELECT extension_id FROM ${JOOMLA_DB_PREFIX}extensions WHERE type='component' AND element='com_fdshop' LIMIT 1")"
[[ "$extension_id" =~ ^[0-9]+$ ]] || { echo 'FDShop-Komponente fehlt.' >&2; exit 1; }
sql "DROP TABLE IF EXISTS ${JOOMLA_DB_PREFIX}fdshop_product_watchlist; UPDATE ${JOOMLA_DB_PREFIX}schemas SET version_id='0.0.40' WHERE extension_id=${extension_id};"
./scripts/fdshop install
[[ "$(sql "SELECT version_id FROM ${JOOMLA_DB_PREFIX}schemas WHERE extension_id=${extension_id}")" == 0.0.41 ]]
[[ "$(sql "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${MARIADB_DATABASE}' AND table_name='${JOOMLA_DB_PREFIX}fdshop_product_watchlist'")" == 1 ]]
echo 'Product interaction migration 0.0.40 -> 0.0.41: PASS'
