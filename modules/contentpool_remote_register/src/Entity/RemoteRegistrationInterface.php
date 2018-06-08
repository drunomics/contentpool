<?php

namespace Drupal\contentpool_remote_register\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Time record entities.
 *
 * @ingroup ems_time_records
 */
interface RemoteRegistrationInterface extends ContentEntityInterface, EntityChangedInterface {

}
