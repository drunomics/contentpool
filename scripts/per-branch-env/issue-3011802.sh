#!/usr/bin/env bash
set -ex

export LAUNCH_SATELLITE_GIT_BRANCH="issue-3011802"
export PRE_BUILD_COMMANDS='composer require drunomics/contentpool_replication:"dev-issue-3011802 as 1.1.0"'

