<?php

namespace Drupal\multiversion_sequence_filter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\replication\Plugin\ReplicationFilterInterface;

/**
 * Optional interface for replication filters.
 */
interface ReplicationFilterValueProviderInterface extends ReplicationFilterInterface {

  /**
   * Gets the entity types (and bundles) to filter for, if any.
   *
   * @return string[]
   *   An array of entity type IDs or combinations of entity type IDs and
   *   bundles concatenated by point. If empty, the filter is skipped.
   */
  public function getConfiguredEntityTypeFilter();

  /**
   * Gets the filter values as configured for the plugin instance.
   *
   * @return string[]
   *   An array of filter values. If empty, the filter is skipped.
   */
  public function getConfiguredFilterValues();

  /**
   * Derives the filter values for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity revision.
   *
   * @return string[]
   *   An array of possible filter values.
   */
  public function deriveFilterValues(EntityInterface $entity);
}
