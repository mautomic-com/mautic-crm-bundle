#!/usr/bin/env bash
# Open the E2E test report in the default browser.
# Requires DDEV to be running with the e2e-report service.

set -euo pipefail

DDEV_HOSTNAME="$(ddev describe -j 2>/dev/null | php -r 'echo json_decode(file_get_contents("php://stdin"))->raw->hostname ?? "localhost";')"
REPORT_URL="https://${DDEV_HOSTNAME}:8090/report.html"

echo "Opening E2E report: $REPORT_URL"

if command -v open &>/dev/null; then
    open "$REPORT_URL"
elif command -v xdg-open &>/dev/null; then
    xdg-open "$REPORT_URL"
else
    echo "Could not detect browser opener. Visit: $REPORT_URL"
fi
