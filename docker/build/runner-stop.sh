#!/bin/bash

##
# Stop & Remove Container
##

THIS_PATH=${BASH_SOURCE%/*}
cd "${THIS_PATH}" || exit 1

source ./runner-config.sh

if ./helper/check-running.sh "${TARGET_CONTAINER}"; then
	echo "Stopping Container ${TARGET_CONTAINER}"
    docker container stop "${TARGET_CONTAINER}" 2>/dev/null
fi

echo "Removing Container"
docker container rm "${TARGET_CONTAINER}"
