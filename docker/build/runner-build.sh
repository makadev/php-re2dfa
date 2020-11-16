#!/bin/bash

##
# Build Image: use ".../runner-build.sh rebuild" for rebuilding
##

THIS_PATH=${BASH_SOURCE%/*}
cd "${THIS_PATH}" || exit 1

source ./runner-config.sh

## set rebuild flag depending of whether the image exists or a rebuild is wanted
REBUILD_IMAGE=true
if [ "$1" != "rebuild" ]; then
	if ./helper/check-image.sh "${TARGET_IMAGE}:${TARGET_VERSION}"; then
		REBUILD_IMAGE=false
	fi
fi

## build if needed/wanted or skip
if [ "$REBUILD_IMAGE" == "true" ]; then
	cd runner && docker build --pull --rm -f Dockerfile -t ${TARGET_IMAGE}:${TARGET_VERSION} \
	  --build-arg PHP_CLI_IMAGE=${USE_PHP_CLI} \
	  --build-arg COMPOSER_IMAGE=${USE_COMPOSER} \
		.
fi
