#!/bin/bash
set -ex
cd `dirname $0`/..

source ./scripts/util/per-branch-env.sh
./scripts/util/install-phapp.sh

[ ! -d ../contentpool-client ] || (echo "Old contentpool-client is still existing, please remove ../contentpool-client." && exit 1)

# Set GIT_BRANCH parameter to avoid inheriting it from the main repository.
export GIT_BRANCH=${LAUNCH_SATELLITE_GIT_BRANCH:-8.x-1.x}

cd ..
git clone https://github.com/drunomics/contentpool-client.git --branch=$GIT_BRANCH contentpool-client

./contentpool-client/scripts/create-project.sh
./contentpool-client/scripts/run-server.sh
./contentpool-client/scripts/init-project.sh

echo "Contentpool satellite project launched."
echo "SUCCESS."
