#!/usr/bin/env bash
# Open the E2E test report in the default browser.
# Requires DDEV to be running with the e2e-report service.

set -euo pipefail

REPORT_URL="https://mautic-001.ddev.site:8090/report.html"

echo "Opening E2E report: $REPORT_URL"

if command -v open &>/dev/null; then
    open "$REPORT_URL"
elif command -v xdg-open &>/dev/null; then
    xdg-open "$REPORT_URL"
else
    echo "Could not detect browser opener. Visit: $REPORT_URL"
fi
