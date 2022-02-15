#!/usr/bin/env bash

# Exit immediately if a command exits with a non-zero status
set -e

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" > /dev/null 2>&1 && pwd )"
cd $DIR/..

unlink ./tmp/reports/codesniffer/report.xml > /dev/null 2>&1 || true
./vendor/bin/phpcs --config-set show_progress 1  > /dev/null 2>&1 || true

./vendor/bin/phpcs --colors --report=full --standard=PSR12 ./src/*
