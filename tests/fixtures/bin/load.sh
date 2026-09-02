#!/usr/bin/env bash
set -Eeuo pipefail
readonly FIXTURE_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
readonly REPO_ROOT="$(cd -- "$FIXTURE_DIR/../.." && pwd)"
readonly COMPOSE_PROJECT=fdshop
readonly COMPOSE_FILE="$REPO_ROOT/compose.yaml"
readonly ENV_FILE="$REPO_ROOT/.env"

compose() { docker compose --project-name "$COMPOSE_PROJECT" --file "$COMPOSE_FILE" --env-file "$ENV_FILE" "$@"; }
fail() { printf 'Fixture safety check failed: %s\n' "$1" >&2; exit 1; }
sql() { compose exec -T --env "MYSQL_PWD=${MARIADB_PASSWORD}" db mariadb --batch --skip-column-names --user="$MARIADB_USER" "$MARIADB_DATABASE" --execute "$1"; }

cd "$REPO_ROOT"
[[ -f "$ENV_FILE" ]] || fail '.env missing'
set -a; source "$ENV_FILE"; set +a
[[ "$COMPOSE_PROJECT" == fdshop ]] || fail 'unexpected Compose project'
[[ "${MARIADB_DATABASE:-}" == fdshop ]] || fail 'MARIADB_DATABASE must be fdshop'
[[ -n "${MARIADB_USER:-}" && -n "${MARIADB_PASSWORD:-}" ]] || fail 'database credentials missing'
[[ "${JOOMLA_DB_PREFIX:-fd_}" =~ ^[A-Za-z0-9_]+$ ]] || fail 'unsafe Joomla DB prefix'
[[ -n "${JOOMLA_ADMIN_USERNAME:-}" ]] || fail 'JOOMLA_ADMIN_USERNAME missing'
[[ "$JOOMLA_ADMIN_USERNAME" =~ ^[A-Za-z0-9_.-]+$ ]] || fail 'unsafe Joomla administrator username'

project_label="$(compose ps --format json db | grep -o '"Project":"[^"]*"' | head -1 | cut -d'"' -f4)"
[[ "$project_label" == fdshop ]] || fail 'running database is not in Compose project fdshop'
joomla_db_host="$(compose exec -T joomla printenv JOOMLA_DB_HOST)"
[[ "$joomla_db_host" == db:3306 ]] || fail 'external Joomla database host detected'
published_db_ports="$(docker inspect fdshop-db-1 --format '{{json .NetworkSettings.Ports}}')"
[[ "$published_db_ports" == *'"3306/tcp":null'* ]] || fail 'database port is published externally'
actual_db="$(sql 'SELECT DATABASE();')"
[[ "$actual_db" == fdshop ]] || fail 'connected database is not fdshop'

extension_id="$(sql "SELECT extension_id FROM ${JOOMLA_DB_PREFIX}extensions WHERE type='component' AND element='com_fdshop' LIMIT 1;")"
[[ "$extension_id" =~ ^[0-9]+$ ]] || fail 'FDShop is not installed'
schema_version="$(sql "SELECT version_id FROM ${JOOMLA_DB_PREFIX}schemas WHERE extension_id=${extension_id};")"
expected_schema="$(python3 -c 'import json; print(json.load(open("tests/fixtures/expected.json"))["schemaVersion"])')"
[[ "$schema_version" == "$expected_schema" ]] || fail "expected schema $expected_schema, found $schema_version"
admin_user_id="$(sql "SELECT id FROM ${JOOMLA_DB_PREFIX}users WHERE username='${JOOMLA_ADMIN_USERNAME}' LIMIT 1;")"
[[ "$admin_user_id" =~ ^[0-9]+$ ]] || fail 'sandbox administrator user missing'

rendered_sql="$(mktemp -t fdshop-fixture.XXXXXXXX.sql)"
trap '[[ -n "${rendered_sql:-}" && "$rendered_sql" == /tmp/fdshop-fixture.* ]] && rm -f -- "$rendered_sql"' EXIT
sed -e "s/__PREFIX__/${JOOMLA_DB_PREFIX}/g" -e "s/__JOOMLA_USER_ID__/${admin_user_id}/g" "$FIXTURE_DIR/base.sql" >"$rendered_sql"
compose exec -T --env "MYSQL_PWD=${MARIADB_PASSWORD}" db mariadb --user="$MARIADB_USER" "$MARIADB_DATABASE" <"$rendered_sql"

compose exec -T --user root joomla install -d -o www-data -g www-data -m 0755 /var/www/html/images/FDShop/products
compose exec -T --user root joomla find /var/www/html/images/FDShop/products -maxdepth 1 -type f -name 'e2e-fixture-*' -delete
compose exec -T --user root joomla install -o www-data -g www-data -m 0644 /workspace/fdshop/tests/fixtures/files/e2e-fixture-product.svg /var/www/html/images/FDShop/products/e2e-fixture-product.svg

bash "$FIXTURE_DIR/bin/verify.sh"
printf 'FDShop fixtures loaded and verified.\n'
