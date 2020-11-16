#!/bin/bash

echo "Checking if Container $1 is running"

if docker exec "$1" true 2>/dev/null; then
    echo "Container is Running"
    exit 0
else
    echo "Container is not Running"
    exit 1
fi
