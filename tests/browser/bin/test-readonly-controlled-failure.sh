#!/usr/bin/env bash
set -Eeuo pipefail

readonly SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
readonly REPO_ROOT="$(cd -- "$SCRIPT_DIR/../../.." && pwd)"
readonly RUN_ID="readonly-controlled-failure-$$"
readonly RESULT_DIR="$REPO_ROOT/.docker/test-results/playwright/$RUN_ID"
output_file="$(mktemp -t fdshop-readonly-controlled-failure.XXXXXXXX)"
trap 'rm -f -- "$output_file"' EXIT

set +e
FDSHOP_READONLY_RUN_ID="$RUN_ID" FDSHOP_READONLY_INJECT_FAILURE=wrong-dashboard-title \
  bash "$REPO_ROOT/scripts/fdshop" browser-readonly >"$output_file" 2>&1
exit_code=$?
set -e
cat "$output_file"
printf 'Controlled read-only exit code: %s\n' "$exit_code"

if [[ "$exit_code" -eq 0 ]]; then
  printf 'Controlled read-only failure was not detected.\n' >&2
  exit 1
fi
if grep -q 'FDShop browser read-only suite: PASS' "$output_file"; then
  printf 'Controlled read-only failure emitted a false PASS.\n' >&2
  exit 1
fi
if ! grep -q 'FDShop browser read-only suite: FAIL' "$output_file"; then
  printf 'Controlled read-only failure emitted no FAIL result.\n' >&2
  exit 1
fi
screenshot_count="$(find "$RESULT_DIR" -type f -name '*.png' | wc -l)"
trace_count="$(find "$RESULT_DIR" -type f -name 'trace.zip' | wc -l)"
if [[ "$screenshot_count" -lt 1 ]]; then
  printf 'Controlled read-only failure produced no screenshot.\n' >&2
  exit 1
fi
if [[ "$trace_count" -lt 1 ]]; then
  printf 'Controlled read-only failure produced no trace.\n' >&2
  exit 1
fi
printf 'Controlled read-only artifacts: screenshots=%s traces=%s\n' "$screenshot_count" "$trace_count"
printf 'Controlled read-only failure detection: PASS\n'
