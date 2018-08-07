<?php

namespace Drupal\contentpool_remote_register;

/**
 * Allows setter injection and simple usage of the service.
 */
trait PushManagerTrait {

  /**
   * The push manager.
   *
   * @var \Drupal\contentpool_remote_register\PushManagerInterface
   */
  protected $pushManager;

  /**
   * Sets the push manager object to use.
   *
   * @param \Drupal\contentpool_remote_register\PushManagerInterface $push_manager
   *   The push manager object.
   *
   * @return $this
   */
  public function setPushManagerInterface(PushManagerInterface $push_manager) {
    $this->pushManager = $push_manager;
    return $this;
  }

  /**
   * Gets the push manager.
   *
   * @return \Drupal\contentpool_remote_register\PushManagerInterface
   *   The push manager.
   */
  protected function getPushManager() {
    if (!$this->pushManager) {
      $this->pushManager = \Drupal::service('contentpool_remote_register.push_manager');
    }
    return $this->pushManager;
  }

}
