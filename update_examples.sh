#!/bin/bash

THIS_PATH=${BASH_SOURCE%/*}
cd "${THIS_PATH}" || exit 1

sudo apt update \
 && sudo apt install graphviz \
 && for filename in examples/cli/*.spec; do bin/re2dfa-cli -f "$filename" -t json-tables > "${filename%.spec}.json"; done \
 && for filename in examples/cli/*.spec; do bin/re2dfa-cli -f "$filename" -t php-tables > "${filename%.spec}.php"; done \
 && for filename in examples/cli/*.spec; do bin/re2dfa-cli -f "$filename" -t dot-graph > "${filename%.spec}.dot"; done \
 && for filename in examples/cli/*.dot; do dot -Tsvg "$filename" -o "${filename%.dot}.svg"; done
