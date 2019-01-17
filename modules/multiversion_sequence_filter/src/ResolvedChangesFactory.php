<?php

namespace Drupal\multiversion_sequence_filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\multiversion_sequence_filter\Changes\ResolvedChanges;
use Drupal\replication\ChangesFactoryInterface;
use Drupal\replication\Plugin\ReplicationFilterManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Overridden changes factory to instantiate a custom class.
 */
class ResolvedChangesFactory implements ChangesFactoryInterface {

  /**
   * @var \Drupal\multiversion_sequence_filter\FilteredSequenceIndex
   */
  protected $sequenceIndex;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * @var \Drupal\replication\Plugin\ReplicationFilterManagerInterface
   */
  protected $filterManager;

  /**
   * @var \Drupal\replication\Changes\ResolvedChanges[]
   */
  protected $instances = [];

  /**
   * Constructs the object.
   *
   * @param \Drupal\multiversion_sequence_filter\FilteredSequenceIndex $sequence_index
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   * @param \Drupal\replication\Plugin\ReplicationFilterManagerInterface $filter_manager
   */
  public function __construct(FilteredSequenceIndex $sequence_index, EntityTypeManagerInterface $entity_type_manager, SerializerInterface $serializer, ReplicationFilterManagerInterface $filter_manager) {
    $this->sequenceIndex = $sequence_index;
    $this->entityTypeManager = $entity_type_manager;
    $this->serializer = $serializer;
    $this->filterManager = $filter_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function get(WorkspaceInterface $workspace) {
    if (!isset($this->instances[$workspace->id()])) {
      $this->instances[$workspace->id()] = new ResolvedChanges(
        $this->sequenceIndex,
        $workspace,
        $this->entityTypeManager,
        $this->serializer,
        $this->filterManager
      );
    }
    return $this->instances[$workspace->id()];
  }

}
