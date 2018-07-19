<?php

namespace Drupal\contentpool_remote_register\Commands;

use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class ContentpoolRemoteRegistrationCommands extends DrushCommands {

  /**
   * Command description here.
   *
   * @usage contentpool-client:pull-content
   *   Usage description
   *
   * @command contentpool-client:pull-content
   * @aliases cpc
   */
  public function pushContent() {
    /** @var \Drupal\contentpool_remote_register\RemotePullManagerInterface $registration_push_manager */
    $remote_pull_manager = \Drupal::service('contentpool_client.remote_pull_manager');
    $push_count = $remote_pull_manager->pullFromRemoteRegistrations();

    drush_print("Tried to init pull from {$push_count} remote registrations");
  }

}
