<?php

namespace Drupal\contentpool_remote_register\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

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
   * @param string $name
   *   The name of the remote registration.
   */
  public function setName($name);

  /**
   * Gets the url of the remote registration.
   *
   * @return string
   *   The url of the remote.
   */
  public function getUrl();

  /**
   * Sets the url of the remote registration.
   *
   * @param string $url
   *   The url of the remote.
   */
  public function setUrl($url);

  /**
   * Gets the uuid of the remote site.
   *
   * @return string
   */
  public function getSiteUuid();

  /**
   * Returns the full Uri for requests to remote.
   *
   * @return string
   */
  public function getEndpointUri();

}
