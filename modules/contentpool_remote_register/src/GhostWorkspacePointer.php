<?php

namespace Drupal\contentpool_remote_register;

use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\replication\ReplicationTask\ReplicationTaskInterface;
use Drupal\workspace\Entity\WorkspacePointer;
use Drupal\workspace\WorkspacePointerInterface;

class GhostWorkspacePointer extends WorkspacePointer {

  /**
   * @var string
   */
  protected $uri;

  /**
   * @var string
   */
  protected $databaseId;

  /**
   * @var string
   */
  protected $name;

  /**
   * {@inheritdoc}
   */
  public function __construct($name, $uri, $database_id) {
    // Nothing to construct.
    $this->name = $name;
    $this->uri = $uri;
    $this->databaseId = $database_id;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return 'Ghost pointer to: ' . $this->name . ' : ' . $this->getDatabaseId();
  }

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
    return $this->databaseId;
  }

  /**
   * {@inheritdoc}
   */
  public function generateReplicationId(WorkspacePointerInterface $target, ReplicationTaskInterface $task = NULL) {
    $target_name = $target->label();
    if ($target->getWorkspace() instanceof WorkspaceInterface) {
      $target_name = $target->getWorkspace()->getMachineName();
    }

    if ($task) {
      return \md5(
        $this->name .
        $target_name .
        var_export($task->getDocIds(), TRUE) .
        ($task->getCreateTarget() ? '1' : '0') .
        ($task->getContinuous() ? '1' : '0') .
        $task->getFilter() .
        '' .
        $task->getStyle() .
        var_export($task->getHeartbeat(), TRUE)
      );
    }
    return \md5(
      $this->name .
      $target_name
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $values = []) {
    // Don't set anything.
  }

  /**
   * {@inheritdoc}
   */
  public function set($field_name, $value, $notify = TRUE) {
    // Don't set anything.
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkspace() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkspaceId() {
    return NULL;
  }

}