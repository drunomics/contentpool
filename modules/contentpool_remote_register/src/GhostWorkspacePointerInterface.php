<?php

namespace Drupal\contentpool_remote_register;

use Drupal\workspace\WorkspacePointerInterface;

interface GhostWorkspacePointerInterface extends WorkspacePointerInterface {

  /**
   * @return string
   */
  public function getUri();

  /**
   * @return string
   */
  public function getDatabaseId();

  /**
   * @param $uri string
   *
   * @return \Drupal\contentpool_remote_register\GhostWorkspacePointerInterface
   */
  public function setUri($uri);

  /**
   * @param $database_id string
   *
   * @return \Drupal\contentpool_remote_register\GhostWorkspacePointerInterface
   */
  public function setDatabaseId($database_id);

}