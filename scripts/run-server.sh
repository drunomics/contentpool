#!/usr/bin/env bash

set -ex
cd `dirname $0`/..

# Run a web server via docker compose.

cd ../contentpool-project/
echo "Running server..."
docker-compose up -d --build
