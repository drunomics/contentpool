<?php

namespace Drupal\contentpool_remote_register\Commands;

use Drupal\contentpool_remote_register\PushManagerTrait;
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

  use PushManagerTrait;

  /**
   * Initializes pushing to all remote registrations.
   *
   * @usage contentpool:push-content
   *
   * @command contentpool:push-content
   * @aliases cppush
   */
  public function pushContent() {
    $push_count = $this->getPushManager()->pushToRegisteredRemotes();

    drush_print("Initialized push to {$push_count} remote registrations");
  }

}
