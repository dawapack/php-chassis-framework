#!/usr/bin/env bash

# Exit immediately if a command exits with a non-zero status
set -e

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
cd $DIR/..

unlink ./tmp/reports/tests/report.xml >/dev/null 2>&1 || true
./vendor/bin/phpunit --log-junit ./tmp/reports/tests/report.xml
