#!/usr/bin/env bash
# Adds support for per-branch environment variable overrides.
# Usage: source scripts/util/per-branch-env.sh

# Determine current dir.
DIR=$( dirname "${BASH_SOURCE[0]}" )
VCS_DIR="$DIR/../.."
source $DIR/get-branch.sh

if [[ -f $VCS_DIR/scripts/per-branch-env/${GIT_BRANCH/\//--}.sh ]]; then
  echo "Loading custom environment variables for branch $GIT_BRANCH"
  source $VCS_DIR/scripts/per-branch-env/${GIT_BRANCH/\//--}.sh
fi
