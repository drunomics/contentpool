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

INSTALL_PROFILE_DIR=`basename $PWD`
source scripts/util/get-branch.sh

echo "Adding distribution..."
composer config repositories.self vcs ../$INSTALL_PROFILE_DIR
composer require drunomics/contentpool:"dev-$GIT_CURRENT_BRANCH"

echo Project created.

echo "Adding custom environment variables..."

cat - >> .defaults.env <<END
  INSTALL_PROFILE=contentpool
END

echo "Setting up project..."
phapp setup localdev

if [[ -f ../$INSTALL_PROFILE_DIR/scripts/per-branch-pre-build-hook/${GIT_BRANCH/\//--}.sh ]]; then
  echo "Executing pre-build hook for branch $GIT_BRANCH"
  ../$INSTALL_PROFILE_DIR/scripts/per-branch-pre-build-hook/${GIT_BRANCH/\//--}.sh
fi

# Run build on the host so we can leverage build caches.
phapp build

echo "Installed project with the following vendors:"
composer show
