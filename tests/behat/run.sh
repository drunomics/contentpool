#!/usr/bin/env bash

# Ease running behat from the vagrant environment by launching chrome.
if [[ $PHAPP_ENV = vagrant ]]; then
  ARGS=""
  if [[ $HEADLESS = "1" ]]; then
    ARGS="--disable-gpu --headless"
  fi
  (google-chrome-stable $ARGS --remote-debugging-address=127.0.0.1 --remote-debugging-port=9222 )&
fi

cd `dirname $0`/../..
CONFIG_FILE=$(realpath tests/behat/behat.yml)
VCS_DIR=../../../..
cd $VCS_DIR

if [[ -z $BEHAT_BASE_URL ]]; then
  source dotenv/loader.sh
fi

# Build up BEHAT_PARAMS for running behat.
# Note that everything set via BEHAT_PARAMS must be excluded in behat.yml as
# BEHAT_PARAMS serves as default fallback only.
CHROME_URL=http://${BEHAT_CHROME_HOST:-chrome}:${BEHAT_CHROME_PORT:-9222}
BASE_URL=${BEHAT_BASE_URL:-$PHAPP_BASE_URL}

BEHAT_PARAMS='{"extensions" : {"Behat\\MinkExtension" : {'
BEHAT_PARAMS+="\"base_url\" : \"$BASE_URL\","
BEHAT_PARAMS+="\"javascript_session\" : \"chrome\","
BEHAT_PARAMS+="\"chrome\" : {\"api_url\" : \"$CHROME_URL\"}"
BEHAT_PARAMS+='}}}'

export BEHAT_PARAMS

# Output some debug information.
echo "Running behat tests with chrome URL $CHROME_URL and base URL $BASE_URL..."

# Finally, run...
vendor/bin/behat -c $CONFIG_FILE --colors $@

if [[ $PHAPP_ENV = vagrant ]]; then
  # End with stopping all sub-process; i.e. chrome.
  [[ -z "$(jobs -p)" ]] || kill $(jobs -p)
fi
