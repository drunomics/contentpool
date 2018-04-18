#!/bin/bash

PHAPP_VERSION=0.6.0-beta10

set -e
cd `dirname $0`/../tests

if ! command -v phapp > /dev/null; then
  echo Installing phapp...
  curl -L https://github.com/drunomics/phapp-cli/releases/download/$PHAPP_VERSION/phapp.phar > phapp
  chmod +x phapp
  sudo mv phapp /usr/local/bin/phapp
else
  echo Phapp version `phapp --version` found.
fi

[ ! -d project ] || (echo "Old project is still existing, please remove tests/project." && exit 1)

phapp create --template=drunomics/drupal-project contentpool_project project --no-interaction


echo ok.