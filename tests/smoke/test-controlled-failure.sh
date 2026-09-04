#!/usr/bin/env bash
set -Eeuo pipefail

readonly SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
readonly REPO_ROOT="$(cd -- "$SCRIPT_DIR/../.." && pwd)"
output_file="$(mktemp -t fdshop-smoke-controlled-failure.XXXXXXXX)"
trap 'rm -f -- "$output_file"' EXIT

set +e
FDSHOP_SMOKE_INJECT_FAILURE=joomla-http bash "$REPO_ROOT/scripts/fdshop" smoke >"$output_file" 2>&1
exit_code=$?
set -e
cat "$output_file"
printf 'Controlled smoke exit code: %s\n' "$exit_code"

if [[ "$exit_code" -eq 0 ]]; then
  printf 'Controlled smoke failure was not detected.\n' >&2
  exit 1
fi
if grep -q 'FDShop smoke test: PASS' "$output_file"; then
  printf 'Controlled smoke failure emitted a false PASS.\n' >&2
  exit 1
fi
if ! grep -q 'FDShop smoke test: FAIL' "$output_file"; then
  printf 'Controlled smoke failure emitted no FAIL result.\n' >&2
  exit 1
fi
printf 'Controlled smoke failure detection: PASS\n'
