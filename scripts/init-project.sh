#!/usr/bin/env bash
cd `dirname $0`/../
set -ex

cd ../contentpool-project
source dotenv/loader.sh

# Then install in the container.
docker-compose exec web phapp install --no-build

# Add demo content.
docker-compose exec web drush en contentpool_demo_content -y

# Add replicator password for testing purposes.
docker-compose exec web drush upwd replicator changeme
