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
   * Initializes pull from all remote registrations.
   *
   * @usage contentpool-client:pull-content
   *
   * @command contentpool-client:pull-content
   * @aliases cpc
   */
  public function pushContent() {
    /** @var \Drupal\contentpool_remote_register\PushManagerInterface $push_manager */
    $push_manager = \Drupal::service('contentpool_client.push_manager');
    $push_count = $push_manager->pushToRegisteredRemotes();

    drush_print("Initialized pull from {$push_count} remote registrations");
  }

}
