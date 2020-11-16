#!/bin/bash

##
# Exec Test + Coverage
##

THIS_PATH=${BASH_SOURCE%/*}
cd "${THIS_PATH}" || exit 1

./runner-exec.sh "/usr/local/bin/phpunit-coverage"
