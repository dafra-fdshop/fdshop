#!/usr/bin/env bash
set -Eeuo pipefail

readonly SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
readonly REPO_ROOT="$(cd -- "$SCRIPT_DIR/../.." && pwd)"
readonly COMPOSE_PROJECT=fdshop
readonly COMPOSE_FILE="$REPO_ROOT/compose.yaml"
readonly ENV_FILE="$REPO_ROOT/.env"
readonly SERIOUS_PATTERN='PHP Fatal error|Uncaught (Error|Exception)|uncaught (error|exception)|SQLSTATE\[|mysqli_sql_exception|Joomla[^:]*: error'

compose() { docker compose --project-name "$COMPOSE_PROJECT" --file "$COMPOSE_FILE" --env-file "$ENV_FILE" "$@"; }
fail() { printf 'FDShop smoke test: FAIL (%s)\n' "$1" >&2; exit 1; }
sql() { compose exec -T --env "MYSQL_PWD=${MARIADB_PASSWORD}" db mariadb --batch --skip-column-names --user="$MARIADB_USER" "$MARIADB_DATABASE" --execute "$1"; }

cd "$REPO_ROOT"
[[ -f "$ENV_FILE" ]] || fail '.env missing'
set -a; source "$ENV_FILE"; set +a
[[ "${MARIADB_DATABASE:-}" == fdshop ]] || fail 'unexpected database'
[[ "${JOOMLA_HTTP_PORT:-8080}" =~ ^[0-9]+$ ]] || fail 'invalid Joomla port'
[[ "${MAILPIT_HTTP_PORT:-8025}" =~ ^[0-9]+$ ]] || fail 'invalid Mailpit port'

start_time="$(date -u +%Y-%m-%dT%H:%M:%S.%NZ)"
run_id="$(date -u +%Y%m%dT%H%M%S)-$$"
joomla_baseline="/tmp/fdshop-smoke-${run_id}.sizes"
new_logs="$(mktemp -t fdshop-smoke-logs.XXXXXXXX)"
trap 'rm -f -- "$new_logs"; compose exec -T joomla rm -f -- "$joomla_baseline" >/dev/null 2>&1 || true' EXIT

compose_state="$(compose ps --format json)"
python3 -c '
import json, sys
rows=[json.loads(line) for line in sys.stdin if line.strip()]
expected={"db","joomla","mailpit"}
services={row.get("Service") for row in rows}
if services != expected:
    raise SystemExit(f"expected services {sorted(expected)}, found {sorted(services)}")
for row in rows:
    if row.get("Project") != "fdshop":
        raise SystemExit(f"unexpected Compose project for {row.get(chr(83)+chr(101)+chr(114)+chr(118)+chr(105)+chr(99)+chr(101))}")
    if row.get("State") != "running" or row.get("Health") != "healthy":
        raise SystemExit(f"unhealthy service {row.get(chr(83)+chr(101)+chr(114)+chr(118)+chr(105)+chr(99)+chr(101))}: {row.get(chr(83)+chr(116)+chr(97)+chr(116)+chr(117)+chr(115))}")
' <<<"$compose_state" || fail 'Compose services missing or unhealthy'

compose exec -T joomla sh -c '
  target="$1"
  : >"$target"
  find /var/www/html/administrator/logs -maxdepth 1 -type f -exec sh -c '\''
    target="$1"; shift
    for file do printf "%s|%s\n" "$file" "$(stat -c %s "$file")" >>"$target"; done
  '\'' sh "$target" {} +
' sh "$joomla_baseline"

http_check() {
  local label="$1" url="$2" expected="$3" actual
  actual="$(curl --silent --show-error --output /dev/null --write-out '%{http_code}' --max-time 15 "$url")" || fail "$label unreachable"
  [[ "$actual" == "$expected" ]] || fail "$label expected HTTP $expected, got $actual"
}

frontend_expected=200
[[ "${FDSHOP_SMOKE_INJECT_FAILURE:-}" == joomla-http ]] && frontend_expected=418
[[ -z "${FDSHOP_SMOKE_INJECT_FAILURE:-}" || "${FDSHOP_SMOKE_INJECT_FAILURE:-}" == joomla-http ]] || fail 'unknown test failure injection'
http_check 'Joomla frontend' "http://127.0.0.1:${JOOMLA_HTTP_PORT:-8080}/" "$frontend_expected"
http_check 'Joomla administrator' "http://127.0.0.1:${JOOMLA_HTTP_PORT:-8080}/administrator/" 200
http_check 'Mailpit' "http://127.0.0.1:${MAILPIT_HTTP_PORT:-8025}/" 200

bash "$REPO_ROOT/scripts/fdshop" verify-install >/dev/null
fixture_output="$(bash "$REPO_ROOT/scripts/fdshop" fixtures-verify)" || fail 'fixtures invalid or missing'

compose logs --no-color --since "$start_time" >"$new_logs"
compose exec -T joomla sh -c '
  baseline="$1"
  find /var/www/html/administrator/logs -maxdepth 1 -type f -exec sh -c '\''
    baseline="$1"; shift
    for file do
      old="$(grep -F "${file}|" "$baseline" | head -1 | cut -d "|" -f2 || true)"
      old="${old:-0}"
      size="$(stat -c %s "$file")"
      if [ "$size" -gt "$old" ]; then dd if="$file" bs=1 skip="$old" status=none; fi
    done
  '\'' sh "$baseline" {} +
' sh "$joomla_baseline" >>"$new_logs" 2>/dev/null || fail 'Joomla log delta unavailable'

if grep -E -i "$SERIOUS_PATTERN" "$new_logs"; then fail 'serious errors produced during this smoke run'; fi

extension_id="$(sql "SELECT extension_id FROM ${JOOMLA_DB_PREFIX:-fd_}extensions WHERE type='component' AND element='com_fdshop' LIMIT 1;")"
schema_version="$(sql "SELECT version_id FROM ${JOOMLA_DB_PREFIX:-fd_}schemas WHERE extension_id=${extension_id};")"
table_count="$(sql "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${MARIADB_DATABASE}' AND table_name LIKE '${JOOMLA_DB_PREFIX:-fd_}fdshop\\_%';")"

printf '%s\n' \
  'Docker services: OK' \
  'Joomla HTTP: OK' \
  'Administrator HTTP: OK' \
  'Mailpit HTTP: OK' \
  "FDShop extension: OK (extension_id=${extension_id})" \
  "Schema: ${schema_version}" \
  "Tables: ${table_count}/32" \
  'Fixtures: OK' \
  'Serious new log errors: 0' \
  '' \
  'FDShop smoke test: PASS'
