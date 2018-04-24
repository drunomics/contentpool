#!/usr/bin/env bash

# Run webserver.
# @todo

# Install it.
phapp setup ${PHAPP_ENV:-vagrant}
INSTALL_PROFILE=contentpool phapp install