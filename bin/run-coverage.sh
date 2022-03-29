#!/bin/bash
set -e

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
cd $DIR/..

unlink ./tmp/reports/coverage/report.xml >/dev/null 2>&1 || true
unlink ./tmp/reports/tests/report.xml >/dev/null 2>&1 || true
rm -rf ./tmp/reports/coverage/html/* || true

./vendor/bin/phpunit --testdox \
  --coverage-clover=./tmp/reports/coverage/report.xml \
  --coverage-html=./tmp/reports/coverage/html \
  --log-junit ./tmp/reports/tests/report.xml
