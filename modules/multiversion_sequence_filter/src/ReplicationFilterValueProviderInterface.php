<?php

namespace Drupal\multiversion_sequence_filter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\replication\Plugin\ReplicationFilterInterface;

/**
 * Optional interface for replication filters.
 */
interface ReplicationFilterValueProviderInterface extends ReplicationFilterInterface {

  /**
   * Method that marks a filter instance to support this interface.
   *
   * This marker-method is used to allow plugins to implement this interface
   * without requiring the implementation and thus a module dependency.
   *
   * @return bool
   */
  public function providesFilterValues();

  /**
   * Gets the types (entity types and bundles) to replicate.
   *
   * @return string[]
   *   An array of entity type IDs or combinations of entity type IDs and
   *   bundles concatenated by point. If empty, the filter is skipped.
   */
  public function getUnfilteredTypes();

  /**
   * Gets the types (entity types and bundles) to filter for, if any.
   *
   * @return string[]
   *   An array of entity type IDs or combinations of entity type IDs and
   *   bundles concatenated by point. If empty, the filter is skipped.
   */
  public function getFilteredTypes();

  /**
   * Gets the values to filter for.
   *
   * @return string[]
   *   An array of filter values. If empty, the filter is skipped.
   */
  public function getFilterValues();

  /**
   * Derives the filter values for the given entity.
   *
   * If the derived values match the filter values of ::getFilterValues the
   * entity will be replicated.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity revision.
   *
   * @return string[]
   *   An array of possible filter values.
   */
  public function deriveFilterValues(EntityInterface $entity);
}
