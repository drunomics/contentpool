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
   */
  public function getCount() {
    return $this->connection->select($this->indexTable, 't')
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * Gets a range of sequence entries, while applying filters and additions.
   *
   * @param int $start
   *   The sequence id from where to start.
   * @param int $stop
   *   (optional) The sequence id where to stop.
   * @param array $filter_values
   *   (optional) An array of filter values to apply.
   * @param bool $inclusive
   *   Whether the stopped sequence should be included or not.
   *
   * @return mixed[]
   *   A numerical index array of entry values, sorted by sequence.
   */
  public function getRange($start, $stop = NULL, array $filter_values = [], $inclusive = TRUE) {
    /** @var \Drupal\Core\Database\Query\SelectInterface $main_query */
    $main_query = $this->connection->select($this->indexTable, 'i')
      ->fields('i', ['value'])
      ->condition('seq', $start, $inclusive ? '>=' : '>');
    if ($main_query !== NULL) {
      $main_query->condition('seq', $stop, $inclusive ? '<=' : '<');
    }
    if ($filter_values) {
      $main_query->innerJoin($this->filterTable, 'f', 'i.name=f.name');
      $main_query->condition('v.filter_value', $filter_values, 'IN');
    }
    $main_query->distinct();

    // Add a second select for the additional entries.
    $additions_query = clone $main_query;
    $additions_query->innerJoin($this->additionsTable, 'a', 'i.name=a.name');
    $additions_query->innerJoin($this->indexTable, 'i2', 'a.additional_entry=i2.name');

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
   * @param mixed[] $entries
   *   An array of entries, keyed by entry name. Each entry must have the
   *   following keys:
   *    - seq: (int) The sequence number.
   *    - value: (mixed) The to be serialized value.
   *    - filter_values: (string[]) The array of filter values.
   *    - additional_entries: (string[]) The array of additional entries.
   * @throws \Exception
   */
  public function addMultiple(array $entries) {
    $transaction = $this->connection->startTransaction();

    foreach ($entries as $name => $entry) {
      // Update the index table.
      $this->connection->merge($this->indexTable)
        ->keys([
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
        ->execute();
      $query = $this->connection->insert($this->filterTable)
        ->fields(['name', 'filter_value']);
      foreach ($entry['filter_values'] as $filter_value) {
        $query->values(['name' => $name, 'filter_value' => $filter_value]);
      }
      $query->execute();

      // Update additions.
      $this->connection->delete($this->additionsTable)
        ->condition('name', $name)
        ->execute();
      $query = $this->connection->insert($this->additionsTable)
        ->fields(['name', 'additional_entries']);
      foreach ($entry['additional_entries'] as $entry) {
        $query->values(['name' => $name, 'filter_value' => $entry]);
      }
      $query->execute();
    }
  }

  /**
   * Gets the oldest entry.
   *
   * @return int
   */
  public function getOldestEntry() {
    $query = $this->connection->select($this->indexTable);
    $query->addExpression('MAX(seq)');
    return $query->execute()->fetchField();
  }

  /**
   * Gets the latest entry.
   *
   * @return int
   */
  public function getLatestEntry() {
    $query = $this->connection->select($this->indexTable);
    $query->addExpression('MIN(seq)');
    return $query->execute()->fetchField();
  }

}
