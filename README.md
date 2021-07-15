# Contentpool distribution

[![Build Status](https://travis-ci.org/drunomics/contentpool.svg?branch=8.x-1.x)](https://travis-ci.org/drunomics/contentpool)

 The contentpool distribution combines the publishing features of the Thunder
 distribution with powerful content API & replication features! 
 https://www.drupal.org/project/contentpool 
 
## Status

The distribution proved itself very valuable, but it turned out that replication via multiversion is too complex for our use-case - so we switched to a simpler approach.

If you like to continue maintaining the distribution, please get in touch!
 
## Overview

This repository is the Drupal install profile for the distribution. You'll
need a drupal project for installing it. Refer to "Installation" for details.

Satellite sites can use https://github.com/drunomics/contentpool-client to easily replicate content based upon
configurable replication filters. Content is pulled on a regular basis, while optionally the contentpool pushes
changes instantly to selected satellites.

## Status

The distribution is in early development stages, but basically working already. Stayed tuned!

## Workflow, Issues

Please file issues the drupal.org issue tracker at https://www.drupal.org/project/issues/contentpool.
For suggested code changes, please submit pull requests (PRs) to the respective github repo and link them from your drupal.org issue.

## Installation

### Quick installation

The distribution can be best tested by using the provided scripts to setup a new Drupal project using the distribution.
Furthermore it comes with a ready-to-go docker-compose setup, so you can try the distribution. You'll need once project
for the contentpool and one project for a satellite site that connects to the contentpool.

#### 0. Prerequisites

 * Install [phapp-cli](https://github.com/drunomics/phapp-cli) in version 0.6.7 or higher. If installed, run `phapp self:update` to ensure you have the latest version.
 * Make sure [docker-compose](https://docs.docker.com/compose/) is installed and working.
   Ensure you do *not* use docker-composer version 1.21, as it contains this [regression](https://github.com/docker/compose/issues/5874). Check your version via `docker-compose --version`.
   If so, update to version 1.22 which is known to work. See https://github.com/docker/compose/releases/tag/1.22.0
 * Install [lupus-localdev](https://github.com/drunomics/lupus-localdev) to allow launching multiple projects!

#### 1. Setup contentpool 

Run the following commands:

    # cd to ~/projects or similar.
    git clone git@github.com:drunomics/contentpool.git && cd contentpool
    # Check out tag of latest release or stay with the development version.
    ./scripts/create-project.sh
    ./scripts/run-server.sh
    ./scripts/init-project.sh
    
If all worked, you can access your site at http://contentpool-project.localdev.space
The distribution comes with some basic demo content, which is already added in by
the init-project script. The demo content is provided by the optional module
`contentpool_demo_content`.

If you want to run drush commands, do so from inside the docker container.
Run the following commands from *a newly opened terminal*:

    cd ../contentpool-project
    docker-compose exec cli /bin/bash
    drush uli

#### 2. Setup satellite site:

Run the following commands:

    # cd to ~/projects or similar.
    git clone git@github.com:drunomics/contentpool-client.git && cd contentpool-client
    # Check out tag of latest release or stay with the development version.
    ./scripts/create-project.sh
    ./scripts/run-server.sh
    ./scripts/init-project.sh
    
If all worked, you can access your site at http://satellite-project.localdev.space

If you want to run drush commands, do so from inside the docker container.
Run the following commands from *a newly opened terminal*:

    cd ../satellite-project
    docker-compose exec cli /bin/bash
    drush uli
    
Refer to the [usage documentation](https://github.com/drunomics/contentpool-client#usage) to trigger a first
replication!

### Regular installation

 The install profile can be added to a Drupal 8 site via composer:

      composer require drunomics/contentpool
      
  Then install Drupal with while selecting the "Contentpool" distribution.
  Note that only a composer based installation is supported. Start off with
  a composer-based Drupal project like [drunomics/drupal-project](https://github.com/drunomics/drupal-project).

## Development

  Just follow the above "Quick installation" instructions and edit the install profile
  content at web/profiles/contrib/contentpool. You can make sure it's a Git
  checkout by doing:
      
      rm -rf web/profiles/contrib/contentpool
      composer install --prefer-source

## Running tests

### Locally, via provided scripts
  
 After installation with the provided scripts (see above) you can just launch
 the tests as the following:
 
     ./scripts/create-project.sh
     ./scripts/run-server.sh
     ./scripts/init-project.sh
     ./scripts/run-tests.sh

### Manually

Based upon the manual installation instructions you can launch tests as
follows:

    # Launch tests inside a docker container, so name resolution works thanks to
    # docker host aliases and the PHP environment is controlled by the container.
   docker-compose exec cli ./web/profiles/contrib/contentpool/tests/behat/run.sh


## JSON API

To read more about the JSON api please read the documentation in [docs/api.md](https://github.com/drunomics/contentpool/tree/8.x-1.x/docs/api.md)

## Troubleshooting

 - if you have "Access denied" error on db connection then try to remove docker volume(from contentpool-project folder):
 
       docker-compose down
       docker volume rm contentpool-project_data-volume
       docker-compose up -d

## Credits

 - [Österreichischer Wirtschaftsverlag GmbH](https://www.drupal.org/%C3%B6sterreichischer-wirtschaftsverlag-gmbh): Initiator, Sponsor
 - [drunomics GmbH](https://www.drupal.org/drunomics): Concept, Development, Maintenance
