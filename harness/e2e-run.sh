#!/usr/bin/env bash
# Run E2E acceptance tests for MautomicCrmBundle inside DDEV
# Usage: ./harness/e2e-run.sh [extra-codecept-args...]
#
# Examples:
#   ./harness/e2e-run.sh                          # Run all tests
#   ./harness/e2e-run.sh PipelineManagementCest   # Run single Cest
#   ./harness/e2e-run.sh --steps                  # Show step-by-step output

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
MAUTIC_ROOT="$(cd "$PLUGIN_DIR/../.." && pwd)"

cd "$MAUTIC_ROOT"

echo "=== Building Codeception tester classes ==="
ddev exec vendor/bin/codecept build -c plugins/MautomicCrmBundle/Tests/codeception.yml

echo ""
echo "=== Running E2E acceptance tests ==="
ddev exec vendor/bin/codecept run acceptance \
    -c plugins/MautomicCrmBundle/Tests/codeception.yml \
    --html \
    --steps \
    "$@"

echo ""
echo "=== Done ==="
echo "Report: https://mautic-001.ddev.site:8090/report.html"
echo "Screenshots: plugins/MautomicCrmBundle/Tests/Acceptance/_output/"
