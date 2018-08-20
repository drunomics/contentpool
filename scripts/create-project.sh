#!/bin/bash
set -ex
cd `dirname $0`/..

source ./scripts/util/per-branch-env.sh
./scripts/util/install-phapp.sh

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

if [[ ! -z "$PRE_BUILD_COMMANDS" ]]; then
  echo "Executing pre-build commands for branch $GIT_BRANCH"
  eval "$PRE_BUILD_COMMANDS"
fi

echo "Installed project with the following vendors:"
composer show
