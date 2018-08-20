<?php

namespace Drupal\contentpool_remote_register;

/**
 * Interface for RemoteAutopullManager.
 */
interface PushManagerInterface {

  /**
   * Pushes to all registered remotes.
   */
  public function pushToRegisteredRemotes();

}
