<?php

namespace Drupal\contentpool_remote_register;

/**
 * Interface for RemoteAutopullManager.
 */
interface RegistrationPullManagerInterface {

  /**
   * Pulls from all registered remotes.
   */
  public function pullFromRemoteRegistrations();

}
