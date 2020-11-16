#!/bin/bash

##
# Exec Command in Container
##

THIS_PATH=${BASH_SOURCE%/*}
cd "${THIS_PATH}" || exit 1

./runner-build.sh

source ./runner-config.sh

## startup container if needed
if ! ./helper/check-running.sh "${TARGET_CONTAINER}"; then
	echo "Starting Container ${TARGET_CONTAINER}"
    ## startup container
    docker container start "${TARGET_CONTAINER}" 2>/dev/null || \
    docker run -d --name "${TARGET_CONTAINER}" \
        --volume "$(pwd)/../..":/workspace \
        -it ${TARGET_IMAGE}:${TARGET_VERSION}
fi

## exec given command
echo "Executing Command echo docker exec -it ${TARGET_CONTAINER} bash -c \"$@\""

docker exec -it "${TARGET_CONTAINER}" bash -c "$@"

./runner-stop.sh
