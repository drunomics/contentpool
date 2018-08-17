#!/bin/bash

PHAPP_VERSION=0.6.7

set -e
set -x
cd `dirname $0`/..

if ! command -v phapp > /dev/null; then
  echo Installing phapp...
  curl -L https://github.com/drunomics/phapp-cli/releases/download/$PHAPP_VERSION/phapp.phar > phapp
  chmod +x phapp
  sudo mv phapp /usr/local/bin/phapp
else
  echo Phapp version `phapp --version` found.
fi

[ ! -d ../contentpool-project ] || (echo "Old project is still existing, please remove ../contentpool-project." && exit 1)

phapp create --template=drunomics/drupal-project contentpool-project ../contentpool-project --no-interaction

GIT_COMMIT_HASH=$(git rev-parse HEAD)
GIT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
INSTALL_PROFILE_DIR=`basename $PWD`
cd ../contentpool-project

echo "Adding distribution..."
composer config repositories.self path ../$INSTALL_PROFILE_DIR
composer require drunomics/contentpool:"dev-$GIT_BRANCH#$GIT_COMMIT"

echo Project created.

echo "Adding custom environment variables..."

cat - >> .defaults.env <<END
  INSTALL_PROFILE=contentpool
END

echo "Setting up project..."
phapp setup localdev

echo "Installed project with the following vendors:"
composer show
