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
   * @param string[] $types
   *   (optional) Filter main sequence entries for the given types. For types
   *   like "main-type.sub-type" a value of "main-type" will match also.
   * @param string[] $filtered_types
   *   (optional) Select main sequence entries by this types, which will be
   *   filtered by the given filter values. For types like "main-type.sub-type"
   *   a value of "main-type" will match also.
   * @param array $filterValues
   *   (optional) An array of filter values to apply.
   * @param bool $inclusive
   *   Whether the stopped sequence should be included or not.
   * @param int $limit
   *   (optional) The maximum number of entries to return.
   *
   * @return mixed[]
   *   A numerical index array of entry values, sorted by sequence.
   */
  public function getRange($workspace_id, $start, $stop = NULL, array $types, array $filtered_types, array $filterValues = [], $inclusive = TRUE, $limit = NULL) {
    /** @var \Drupal\Core\Database\Query\SelectInterface $main_query */
    $main_query = $this->connection->select($this->indexTable, 'i')
      ->condition('i.workspace_id', $workspace_id)
      ->condition('i.seq', $start, $inclusive ? '>=' : '>');
    if (isset($stop)) {
      $main_query->condition('i.seq', $stop, $inclusive ? '<=' : '<');
    }
    // Add type filters.
    if ($types || $filtered_types) {
      $condition_group = $main_query->orConditionGroup();
      foreach (array_merge($types, $filtered_types) as $type) {
        $condition_group->condition('i.type', $type . '.%', 'LIKE');
      }
      $main_query->condition($condition_group);
    }
    if ($filterValues) {
      $andCondition = $main_query->andConditionGroup();
      $andCondition->where('i.workspace_id=f.workspace_id AND i.name=f.name');
      $andCondition->condition('f.filter_value', $filterValues, 'IN');
      $main_query->leftJoin($this->filterTable, 'f', $andCondition);

      $condition_group = $main_query->orConditionGroup();
      // Either there is a filter value OR the type must be unfiltered.
      $condition_group->where('f.filter_value IS NOT NULL');
      if ($types) {
        foreach ($types as $type) {
          $condition_group->condition('i.type', $type . '.%', 'LIKE');
        }
      }
      $main_query->condition($condition_group);
    }
    $main_query->distinct();

    // Add a second select for the additional entries.
    $additions_query = clone $main_query;
    $additions_query->innerJoin($this->additionsTable, 'a', 'i.workspace_id=a.workspace_id AND i.name=a.name');
    $additions_query->innerJoin($this->indexTable, 'i2', 'a.workspace_id=i2.workspace_id AND a.additional_entry=i2.name');

    $main_query->fields('i', ['seq', 'value']);
    $additions_query->fields('i2', ['seq', 'value']);
    $main_query->union($additions_query, 'DISTINCT');
    $main_query->orderBy('seq', 'ASC');

    $main_query->range(0, $limit);
    $result = $main_query->execute();

    // Uncomment to use devel module for query debugging.
    // die(dpq($main_query, 1));

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
   *    - type: (string) The type of the entry. Sub-types may be denoted by
   *      delimiting types with points ('.'). May be used for filtering.
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
          // Append a dot to ease filter matches by parent types.
          'type' => $entry['type'] . '.',
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
        ->fields(['workspace_id', 'name', 'additional_entry']);
      foreach ($entry['additional_entries'] as $entry) {
        $query->values(['workspace_id' => $workspace_id, 'name' => $name, 'additional_entry' => $entry]);
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
