#!/usr/bin/env bash
set -Eeuo pipefail
readonly SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
readonly REPO_ROOT="$(cd -- "$SCRIPT_DIR/../.." && pwd)"
cd "$REPO_ROOT"; [[ -f .env ]] || exit 1; set -a; source .env; set +a
[[ "${MARIADB_DATABASE:-}" == fdshop ]] || { echo 'Migrationstest verweigert eine fremde Datenbank.' >&2; exit 1; }
[[ "${JOOMLA_DB_PREFIX:-fd_}" =~ ^[A-Za-z0-9_]+$ ]] || exit 1
compose(){ docker compose --project-name fdshop --file compose.yaml --env-file .env "$@"; }
sql(){ compose exec -T --env "MYSQL_PWD=${MARIADB_PASSWORD}" db mariadb --batch --skip-column-names --user="${MARIADB_USER}" "${MARIADB_DATABASE}" --execute "$1"; }
extension_id="$(sql "SELECT extension_id FROM ${JOOMLA_DB_PREFIX}extensions WHERE type='component' AND element='com_fdshop' LIMIT 1")"
[[ "$extension_id" =~ ^[0-9]+$ ]] || exit 1
sql "SET FOREIGN_KEY_CHECKS=0; DROP TABLE IF EXISTS ${JOOMLA_DB_PREFIX}fdshop_payment_transactions; DROP TABLE IF EXISTS ${JOOMLA_DB_PREFIX}fdshop_payment_reservations; DROP TABLE IF EXISTS ${JOOMLA_DB_PREFIX}fdshop_payment_sessions; SET FOREIGN_KEY_CHECKS=1; ALTER TABLE ${JOOMLA_DB_PREFIX}fdshop_config DROP COLUMN paypal_reservation_minutes; UPDATE ${JOOMLA_DB_PREFIX}schemas SET version_id='0.0.41' WHERE extension_id=${extension_id};"
./scripts/fdshop install
[[ "$(sql "SELECT version_id FROM ${JOOMLA_DB_PREFIX}schemas WHERE extension_id=${extension_id}")" == 0.0.42 ]]
[[ "$(sql "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${MARIADB_DATABASE}' AND table_name IN ('${JOOMLA_DB_PREFIX}fdshop_payment_sessions','${JOOMLA_DB_PREFIX}fdshop_payment_reservations','${JOOMLA_DB_PREFIX}fdshop_payment_transactions')")" == 3 ]]
[[ "$(sql "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='${MARIADB_DATABASE}' AND table_name='${JOOMLA_DB_PREFIX}fdshop_config' AND column_name='paypal_reservation_minutes'")" == 1 ]]
echo 'PayPal payment migration 0.0.41 -> 0.0.42: PASS'
