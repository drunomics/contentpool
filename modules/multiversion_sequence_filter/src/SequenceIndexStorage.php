<?php

namespace Drupal\multiversion_sequence_filter;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Database\Connection;

/**
 * Takes care of sequence index storage and querying.
 *
 * This is based upon \Drupal\key_value\KeyValueStore\DatabaseStorageSortedSet,
 * but it moves away from th keyvalue store concept as suiting in order to
 * reach better performance.
 */
class SequenceIndexStorage {

  /**
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * @var string
   */
  protected $indexTable = 'multiversion_sequence_filter_index';

  /**
   * @var string
   */
  protected $filterTable = 'multiversion_sequence_filter_values';

  /**
   * @var string
   */
  protected $additionsTable = 'multiversion_sequence_filter_additions';

  /**
   * SequenceIndexStorage constructor.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serializer.
   * @param \Drupal\Core\Database\Connection $connection
   *   The db connection to use.
   */
  public function __construct(SerializationInterface $serializer, Connection $connection) {
    $this->serializer = $serializer;
    $this->connection = $connection;
  }

  /**
   *  Gets the number of entries.
   *
   * @param int $workspace_id
   *   The ID of the workspace to use.
   *
   * @return int
   */
  public function getCount($workspace_id) {
    return $this->connection->select($this->indexTable, 't')
      ->condition('workspace_id', $workspace_id)
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * Gets a range of sequence entries, while applying filters and additions.
   *
   * @param int $workspace_id
   *   The ID of the workspace to use.
   * @param int $start
   *   The sequence id from where to start.
   * @param int $stop
   *   (optional) The sequence id where to stop.
   * @param string[] $entityTypeIds
   *   (optional) Filter main sequence entries for the given entity types.
   * @param array $filterValues
   *   (optional) An array of filter values to apply.
   * @param bool $inclusive
   *   Whether the stopped sequence should be included or not.
   *
   * @return mixed[]
   *   A numerical index array of entry values, sorted by sequence.
   */
  public function getRange($workspace_id, $start, $stop = NULL, array $entityTypeIds, array $filterValues = [], $inclusive = TRUE) {
    /** @var \Drupal\Core\Database\Query\SelectInterface $main_query */
    $main_query = $this->connection->select($this->indexTable, 'i')
      ->fields('i', ['value'])
      ->condition('workspace_id', $workspace_id)
      ->condition('seq', $start, $inclusive ? '>=' : '>');
    if ($main_query !== NULL) {
      $main_query->condition('seq', $stop, $inclusive ? '<=' : '<');
    }
    // Add entity type filter to main query, if any.
    if ($entityTypeIds) {
      $condition_group = $main_query->orConditionGroup();
      foreach ($entityTypeIds as $entityTypeId) {
        $condition_group->condition('i.name', $entityTypeId . ':%', 'LIKE');
      }
    }
    if ($filterValues) {
      $main_query->innerJoin($this->filterTable, 'f', 'i.workspace_id=f.workspace_id AND i.name=f.name');
      $main_query->condition('v.filter_value', $filterValues, 'IN');
    }
    $main_query->distinct();

    // Add a second select for the additional entries.
    $additions_query = clone $main_query;
    $additions_query->innerJoin($this->additionsTable, 'a', 'i.workspace_id=a.workspace_id AND i.name=a.name');
    $additions_query->innerJoin($this->indexTable, 'i2', 'a.workspace_id=i2.workspace_id AND a.additional_entry=i2.name');

    $main_query->union($additions_query, 'DISTINCT');
    $main_query->orderBy('seq', 'ASC');

    $result = $main_query->execute();
    $values = [];
    foreach ($result as $item) {
      $values[] = $this->serializer->decode($item->value);
    }
    return $values;
  }

  /**
   * Adds multiple entries.
   *
   * @param int $workspace_id
   *   The ID of the workspace to use.
   * @param mixed[] $entries
   *   An array of entries, keyed by entry name. Each entry must have the
   *   following keys:
   *    - seq: (int) The sequence number.
   *    - value: (mixed) The to be serialized value.
   *    - filter_values: (string[]) The array of filter values.
   *    - additional_entries: (string[]) The array of additional entries.
   * @throws \Exception
   */
  public function addMultiple($workspace_id, array $entries) {
    $transaction = $this->connection->startTransaction();

    foreach ($entries as $name => $entry) {
      // Update the index table.
      $this->connection->merge($this->indexTable)
        ->keys([
          'workspace_id' => $workspace_id,
          'name' => $name,
        ])
        ->fields([
          'seq' => $entry['seq'],
          'value' =>  $this->serializer->encode($entry['value']),
        ])
        ->execute();

      // Update the filter value table.
      $this->connection->delete($this->filterTable)
        ->condition('name', $name)
        ->condition('workspace_id', $workspace_id)
        ->execute();
      $query = $this->connection->insert($this->filterTable)
        ->fields(['workspace_id', 'name', 'filter_value']);
      foreach ($entry['filter_values'] as $filter_value) {
        $query->values(['workspace_id' => $workspace_id, 'name' => $name, 'filter_value' => $filter_value]);
      }
      $query->execute();

      // Update additions.
      $this->connection->delete($this->additionsTable)
        ->condition('name', $name)
        ->condition('workspace_id', $workspace_id)
        ->execute();
      $query = $this->connection->insert($this->additionsTable)
        ->fields(['workspace_id', 'name', 'additional_entries']);
      foreach ($entry['additional_entries'] as $entry) {
        $query->values(['workspace_id' => $workspace_id, 'name' => $name, 'filter_value' => $entry]);
      }
      $query->execute();
    }
  }

  /**
   * Gets the first entry.
   *
   * @param int $workspace_id
   *   The ID of the workspace to use.
   *
   * @return int
   */
  public function getfirstEntry($workspace_id) {
    $query = $this->connection
      ->select($this->indexTable)
      ->condition('workspace_id', $workspace_id);
    $query->addExpression('MIN(seq)');
    return $query->execute()->fetchField();
  }

  /**
   * Gets the last entry.
   *
   * @param int $workspace_id
   *   The ID of the workspace to use.
   *
   * @return int
   */
  public function getLastEntry($workspace_id) {
    $query = $this->connection
      ->select($this->indexTable)
      ->condition('workspace_id', $workspace_id);
    $query->addExpression('MAX(seq)');
    return $query->execute()->fetchField();
  }

}
