<?php

namespace Drupal\contentpool_remote_register;

/**
 * Interface for RemoteAutopullManager.
 */
interface RemotePullManagerInterface {

  /**
   * Pulls from all registered remotes.
   */
  public function pullFromRemoteRegistrations();

}
