#!/usr/bin/env bash
set -e
cd `dirname $0`/../../contentpool-project/
source dotenv/loader.sh

set -x

# Verify coding style.
PHPCS=$(readlink -f vendor/bin/phpcs)
( cd ./web/profiles/contrib/contentpool && $PHPCS --colors --report-width=130 )

# Start chrome container.
docker-compose -f devsetup-docker/service-chrome.yml up -d

# Launch tests inside a docker container, so name resolution works thanks to
# docker host aliases and the PHP environment is controlled by the container.
docker-compose exec web ./web/profiles/contrib/contentpool/tests/behat/run.sh

# Stop chrome container.
docker-compose -f devsetup-docker/service-chrome.yml stop
