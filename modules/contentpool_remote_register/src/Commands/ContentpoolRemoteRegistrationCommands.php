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
    /** @var \Drupal\contentpool_remote_register\RegistrationPushManagerInterface $registration_push_manager */
    $registration_push_manager = \Drupal::service('contentpool_client.remote_pull_manager');
    $push_count = $registration_push_manager->pushToRemoteRegistrations();

    drush_print("Tried to push to {$push_count} remotes");
  }

}
