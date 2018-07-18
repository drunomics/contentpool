<?php

namespace Drupal\contentpool_remote_register;

/**
 * Interface for RemoteAutopullManager.
 */
interface RegistrationPushManagerInterface {

  /**
   * Pulls from all registered remotes.
   */
  public function pushToRemoteRegistrations();

}
