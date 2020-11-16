#!/bin/bash

##
# Exec Test
##

THIS_PATH=${BASH_SOURCE%/*}
cd "${THIS_PATH}" || exit 1

./runner-exec.sh "/usr/local/bin/phpunit-nc --stop-on-error --stop-on-failure --stop-on-warning --stop-on-risky --stop-on-incomplete --stop-on-defect"
