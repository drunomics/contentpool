#!/bin/bash

PHAPP_VERSION=0.6.7

set -e
set -x
cd `dirname $0`/../..

if ! command -v phapp > /dev/null; then
  echo Installing phapp...
  curl -L https://github.com/drunomics/phapp-cli/releases/download/$PHAPP_VERSION/phapp.phar > phapp
  chmod +x phapp
  sudo mv phapp /usr/local/bin/phapp
else
  echo Phapp version `phapp --version` found.
fi

[ ! -d ../contentpool-client ] || (echo "Old contentpool-client is still existing, please remove ../contentpool-client." && exit 1)

git clone https://github.com/drunomics/contentpool-client.git --branch=${LAUNCH_SATELLITE_GIT_BRANCH:-8.x-1.x} contentpool-client

./contentpool-client/scripts/create-project.sh
./contentpool-client/scripts/run-server.sh
./contentpool-client/scripts/init-project.sh

echo "Contentpool satellite project launched."
echo "SUCCESS."
