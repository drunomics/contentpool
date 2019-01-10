<?php

namespace Drupal\multiversion_sequence_filter;

use Drupal\multiversion\Entity\Index\SequenceIndexInterface;

class FilteredSequenceIndex implements SequenceIndexInterface {

  /**
   * @var string
   */
  protected $collectionPrefix = 'multiversion.entity_index.sequence.';

  /**
   * @var string
   */
  protected $workspaceId;

  /**
   * @var \Drupal\key_value\KeyValueStore\KeyValueSortedSetFactoryInterface
   */
  protected $sortedSetFactory;

  /**
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @var \Drupal\multiversion\MultiversionManagerInterface
   */
  protected $multiversionManager;

  /**
   * @param \Drupal\key_value\KeyValueStore\KeyValueSortedSetFactoryInterface $sorted_set_factory
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   * @param \Drupal\multiversion\MultiversionManagerInterface $multiversion_manager
   */
  public function __construct(KeyValueSortedSetFactoryInterface $sorted_set_factory, WorkspaceManagerInterface $workspace_manager, MultiversionManagerInterface $multiversion_manager) {
    $this->sortedSetFactory = $sorted_set_factory;
    $this->workspaceManager = $workspace_manager;
    $this->multiversionManager = $multiversion_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function useWorkspace($id) {
    $this->workspaceId = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function add(ContentEntityInterface $entity) {
    $workspace_id = null;
    $record = $this->buildRecord($entity);
    if ($entity->getEntityType()->get('workspace') === FALSE) {
      $workspace_id = 0;
    }
    $this->sortedSetStore($workspace_id)->add($record['seq'], $record);
  }

  /**
   * {@inheritdoc}
   */
  public function getRange($start, $stop = NULL, $inclusive = TRUE) {
    $range = $this->sortedSetStore()->getRange($start, $stop, $inclusive);
    if (empty($range)) {
      $range = $this->sortedSetStore(0)->getRange($start, $stop, $inclusive);
    }
    return $range;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastSequenceId() {
    $max_score = $this->sortedSetStore()->getMaxScore();
    if (empty($max_score)) {
      $max_score = $this->sortedSetStore(0)->getMaxScore();
    }
    return $max_score;
  }

  /**
   * @param $workspace_id
   * @return \Drupal\key_value\KeyValueStore\KeyValueStoreSortedSetInterface
   */
  protected function sortedSetStore($workspace_id = null) {
    if (!$workspace_id) {
      $workspace_id = $this->workspaceId ?: $this->workspaceManager->getActiveWorkspaceId();
    }
    return $this->sortedSetFactory->get($this->collectionPrefix . $workspace_id);
  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @return array
   */
  protected function buildRecord(ContentEntityInterface $entity) {
    return [
      'entity_type_id' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'entity_uuid' => $entity->uuid(),
      'revision_id' => $entity->getRevisionId(),
      'deleted' => $entity->_deleted->value,
      'rev' => $entity->_rev->value,
      'seq' => $this->multiversionManager->newSequenceId(),
      'local' => (boolean) $entity->getEntityType()->get('local'),
      'is_stub' => (boolean) $entity->_rev->is_stub,
    ];
  }

}
