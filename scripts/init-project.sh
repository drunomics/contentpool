#!/usr/bin/env bash
cd `dirname $0`/../
set -ex

cd ../contentpool-project

# Run build on the host so we can leverage build caches.
phapp build

# Then install in the container.
docker-compose exec cli phapp install --no-build

# Additional field configs from custom_install folder are missing tables
# after installing app, so we do fix them with entity updates command.
docker-compose exec cli drush entity-updates -y

# Add demo content.
docker-compose exec cli drush en contentpool_demo_content -y

# Add replicator password for testing purposes.
docker-compose exec cli drush upwd replicator changeme

# Add admin password for testing purposes.
docker-compose exec cli drush upwd dru_admin changeme

# Generate Oauth2 keys.
docker-compose exec cli drush sogk "../keys"

# Correct permisssion for key files.
docker-compose exec cli chown -R www-data:www-data keys

# Unset maintenance mode.
docker-compose exec cli drush sset system.maintenance_mode 0