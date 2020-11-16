#!/bin/bash

echo "Checking existence of Image $1"

if docker image inspect "$1" > /dev/null; then
    echo "Image Exists"
    exit 0
else
    echo "Image does not Exist"
    exit 1
fi
