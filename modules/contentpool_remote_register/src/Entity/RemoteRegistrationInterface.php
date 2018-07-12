<?php

namespace Drupal\contentpool_remote_register\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining remote registration entities.
 *
 * @ingroup contentpool_remote_register
 */
interface RemoteRegistrationInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the name of the remote registration.
   *
   * @return string
   */
  public function getName();

  /**
   * Sets the name of the remote registration.
   *
   * @param $name
   */
  public function setName($name);

  /**
   * Gets the url of the remote registration.
   *
   * @return string
   */
  public function getUrl();

  /**
   * Sets the url of the remote registration.
   *
   * @param $name
   */
  public function setUrl($url);

  /**
   * Gets the uuid of the remote site.
   *
   * @return string
   */
  public function getSiteUUID();

}
