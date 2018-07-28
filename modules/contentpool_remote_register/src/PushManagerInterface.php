<?php

namespace Drupal\contentpool_remote_register;

/**
 * Interface for RemoteAutopullManager.
 */
interface PushManagerInterface {

  /**
   * Pulls from all registered remotes.
   */
  public function pushToRegisteredRemotes();

}
