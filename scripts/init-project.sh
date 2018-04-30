#!/usr/bin/env bash
cd `dirname $0`/../
set -e
set -x

cd ../contentpool-project
source dotenv/loader.sh

# Run build on the host so we can leverage build caches.
phapp build
# Then install in the container.
docker-compose exec web phapp install --no-build
