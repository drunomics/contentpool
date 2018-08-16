# Contentpool distribution

[![Build Status](https://travis-ci.org/drunomics/contentpool.svg?branch=8.x-1.x)](https://travis-ci.org/drunomics/contentpool)

 The contentpool distribution combines the publishing features of the Thunder
 distribution with powerful content API & replication features! 
 https://www.drupal.org/project/contentpool 
 
## Overview

This repository is the Drupal install profile for the distribution. You'll
need a drupal project for installing it. Refer to "Installation" for details.

## Status

The distribution is in early development stages and not fully working yet. Stayed tuned!

## Installation

### Quick instructions

 The install profile can be added to a Drupal 8 site via composer:

      composer require drunomics/contentpool
      
  Then install Drupal with while selecting the "Contentpool" distribution.
  Note that only a composer based installation is supported. Start off with
  a composer-based Drupal project like [drunomics/drupal-project](https://github.com/drunomics/drupal-project).
  
### Detailled instructions

The following steps can be followed to setup a new site from scratch:

 - Install [drunomics/phapp-cli](https://github.com/drunomics/phapp-cli).
 - Install docker-compose or replace it with your preferred environment below.
 - Follow the following steps:

       phapp create --template=drunomics/drupal-project PROJECT-NAME
       cd PROJECT-NAME
       composer require drunomics/contentpool
       echo "INSTALL_PROFILE=contentpool" >> .defaults.env 
       phapp setup travis
      
   @todo: Rename the environment "travis" to something more fitting.
  
 - Add and install docker-compose setup
 
       git clone https://github.com/drunomics/devsetup-docker.git --branch=1.x devsetup-docker    
       cat - > .docker.defaults.env <<END
         COMPOSE_PROJECT_NAME=contentpool-project
         COMPOSE_FILE=devsetup-docker/docker-compose.yml:devsetup-docker/service-chrome.yml
       END
       source dotenv/loader.sh
       docker-compose up -d
     
 - Install it

       phapp build
       source dotenv/loader.sh
       docker-compose exec web phapp install --no-build
 

## Development

  Just follow the above setup instructions and edit the install profile
  content at web/profiles/contrib/contentpool. You can make sure it's a Git
  checkout by doing:
      
      rm -rf web/profiles/contrib/contentpool
      composer install --prefer-source

## Running tests

### Locally

Based upon the detailled installation instructions you can launch tests as
follows:

    source dotenv/loader.sh
    # Launch tests inside a docker container, so name resolution works thanks to
    # docker host aliases and the PHP environment is controlled by the container.
    docker-compose exec web ./web/profiles/contrib/contentpool/tests/behat/run.sh

### Locally, via travis scripts

 First, ensure do you do not use docker-composer version 1.21, as it contains
 this regression: https://github.com/docker/compose/issues/5874

      docker-compose --version

 If so, update to version 1.22 which is known to work. See
 https://github.com/docker/compose/releases/tag/1.22.0

 Then you can just launch the provided scripts in the same order as travis:
 
     ./scripts/create-project.sh
     ./scripts/run-server.sh
     ./scripts/init-project.sh
     ./scripts/run-tests.sh

## Credits

 - [Österreichischer Wirtschaftsverlag GmbH](https://www.drupal.org/%C3%B6sterreichischer-wirtschaftsverlag-gmbh): Initiator, Sponsor
 - [drunomics GmbH](https://www.drupal.org/drunomics): Concept, Development, Maintenance
