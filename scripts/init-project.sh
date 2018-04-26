#!/usr/bin/env bash
cd `dirname $0`/../
set -e

cd ../contentpool-project
source dotenv/loader.sh
set -x

# Run build on the host so we can leverage build caches.
phapp build
# Then install in the container.
docker-compose exec web phapp install --no-build
