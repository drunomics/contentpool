#!/usr/bin/env bash

set -ex
cd `dirname $0`/..

# Run a web server via docker compose.

cd ../contentpool-project/
echo "Running server..."
docker-compose up -d --build
echo "Waiting for mysql to come up..." && docker-compose exec cli /bin/bash -c "while ! echo exit | nc mariadb 3306; do sleep 1; done" >/dev/null

# Make the php service join the traefik network - this is needed so the satellite can be reached when pushing content.
docker network connect traefik contentpool-project_php_1
