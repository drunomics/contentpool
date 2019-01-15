#!/bin/bash
set -ex
cd `dirname $0`/..

source ./scripts/util/per-branch-env.sh
./scripts/util/install-phapp.sh

[ ! -d ../contentpool-client ] || (echo "Old contentpool-client is still existing, please remove ../contentpool-client." && exit 1)

# Ensure the contentpool GIT_BRANCH variables are not inherited - those
# refer to the pool.
export GIT_BRANCH=${LAUNCH_SATELLITE_GIT_BRANCH:-8.x-1.x}
unset GIT_CURRENT_BRANCH
unset PRE_BUILD_COMMANDS

cd ..
git clone https://github.com/drunomics/contentpool-client.git --branch=$GIT_BRANCH contentpool-client

./contentpool-client/scripts/create-project.sh
./contentpool-client/scripts/run-server.sh
./contentpool-client/scripts/init-project.sh

echo "Contentpool satellite project launched."
echo "SUCCESS."
