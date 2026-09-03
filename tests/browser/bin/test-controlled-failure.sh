#!/usr/bin/env bash
set -Eeuo pipefail

readonly SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
readonly REPO_ROOT="$(cd -- "$SCRIPT_DIR/../../.." && pwd)"
readonly RUN_ID="controlled-failure-$$"
readonly RESULT_DIR="$REPO_ROOT/.docker/test-results/playwright/$RUN_ID"
output_file="$(mktemp -t fdshop-browser-controlled-failure.XXXXXXXX)"
trap 'rm -f -- "$output_file"' EXIT

set +e
FDSHOP_BROWSER_RUN_ID="$RUN_ID" FDSHOP_BROWSER_INJECT_FAILURE=missing-element \
  bash "$REPO_ROOT/scripts/fdshop" browser >"$output_file" 2>&1
exit_code=$?
set -e
cat "$output_file"
printf 'Controlled browser exit code: %s\n' "$exit_code"

if [[ "$exit_code" -eq 0 ]]; then
  printf 'Controlled browser failure was not detected.\n' >&2
  exit 1
fi
if grep -q 'FDShop browser test: PASS' "$output_file"; then
  printf 'Controlled browser failure emitted a false PASS.\n' >&2
  exit 1
fi
if ! grep -q 'FDShop browser test: FAIL' "$output_file"; then
  printf 'Controlled browser failure emitted no FAIL result.\n' >&2
  exit 1
fi
screenshot_count="$(find "$RESULT_DIR" -type f -name '*.png' | wc -l)"
trace_count="$(find "$RESULT_DIR" -type f -name 'trace.zip' | wc -l)"
if [[ "$screenshot_count" -lt 1 ]]; then
  printf 'Controlled failure produced no screenshot.\n' >&2
  exit 1
fi
if [[ "$trace_count" -lt 1 ]]; then
  printf 'Controlled failure produced no trace.\n' >&2
  exit 1
fi
printf 'Controlled browser artifacts: screenshots=%s traces=%s\n' "$screenshot_count" "$trace_count"
printf 'Controlled browser failure detection: PASS\n'
