<?php

namespace Drupal\contentpool_remote_register;

use Drupal\workspace\Entity\WorkspacePointer;

class GhostWorkspacePointer extends WorkspacePointer implements GhostWorkspacePointerInterface {

  /**
   * @var string
   */
  protected $uri;

  /**
   * @var string
   */
  protected $database_id;

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    return $this->uri;
  }

  /**
   * {@inheritdoc}
   */
  public function getDatabaseId() {
    return $this->database_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setUri($uri) {
    $this->uri = $uri;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDatabaseId($database_id) {
    $this->database_id = $database_id;
    return $this;
  }

}