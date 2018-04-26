#!/usr/bin/env bash

set -e
cd `dirname $0`/..

# Run a web service via docker composer

[ ! -f ../contentpool-project/devsetup-docker ] || \
  git clone git@github.com:drunomics/devsetup-docker.git --branch=1.x ../contentpool-project/devsetup-docker

cd ../contentpool-project/

echo "Adding compose environment variables..."
cp devsetup-docker/.env .compose.env

cat - >> .defaults.env <<END
  COMPOSE_PROJECT=contentpool
  COMPOSE_FILE=devsetup-docker/docker-compose.yml:devsetup-docker/service-chrome.yml
END

echo "Running server..."
source dotenv/loader.sh
docker-compose up -d
