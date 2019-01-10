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
   * SequenceIndexStorage constructor.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   * @param \Drupal\Core\Database\Connection $connection
   * @param string $table
   */
  public function __construct(SerializationInterface $serializer, Connection $connection) {
    $this->serializer = $serializer;
    $this->connection = $connection;
  }

  /**
   *  Gets the number of entries..
   */
  public function getCount() {
    return $this->connection->select($this->indexTable, 't')
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * Gets a range of values.
   */
  public function getRange($start, $stop = NULL, $inclusive = TRUE) {
    $query = $this->connection->select($this->indexTable, 't')
      ->fields('t', ['value'])
      ->condition('time', $start, $inclusive ? '>=' : '>');

    if ($stop !== NULL) {
      $query->condition('time', $stop, $inclusive ? '<=' : '<');
    }
    $result = $query->orderBy('time', 'ASC')->execute();

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
   *    - time (timestamp)
   *    - value (mixed, will be serialized)
   *
   * @throws \Exception
   */
  public function addMultiple(array $entries) {
    // @todo Find out if we can to multiple merge queries in one atomic
    // operation.
    foreach ($entries as $name => $entry) {
      foreach ($entry as $score => $member) {
        $this->connection->merge($this->indexTable)
          ->keys([
            'name' => $name,
          ])
          ->fields([
            'time' => $entry['time'],
            'value' =>  $this->serializer->encode($entry['value']),
          ])
          ->execute();
      }
    }
  }

  /**
   * Gets the oldest entry.
   *
   * @return int
   */
  public function getOldestEntry() {
    $query = $this->connection->select($this->indexTable);
    $query->addExpression('MAX(time)');
    return $query->execute()->fetchField();
  }

  /**
   * Gets the latest entry.
   *
   * @return int
   */
  public function getLatestEntry() {
    $query = $this->connection->select($this->indexTable);
    $query->addExpression('MIN(time)');
    return $query->execute()->fetchField();
  }

}
