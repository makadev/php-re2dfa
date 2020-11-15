#!/bin/bash

##
# Exec Test
##

THIS_PATH=${BASH_SOURCE%/*}
cd "${THIS_PATH}" || exit 1

./runner-exec.sh "/usr/local/bin/psalm-check"
