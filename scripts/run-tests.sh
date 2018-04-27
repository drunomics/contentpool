#!/usr/bin/env bash
set -e
cd `dirname $0`/../../contentpool-project/
source dotenv/loader.sh

set -x
# Launch tests inside a docker container, so name resolution works thanks to
# docker host aliases and the PHP environment is controlled by the container.
docker-compose exec web ./web/profiles/contrib/contentpool/tests/behat/run.sh
