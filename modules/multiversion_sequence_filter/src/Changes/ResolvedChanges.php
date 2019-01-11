<?php

namespace Drupal\multiversion_sequence_filter\Changes;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\multiversion_sequence_filter\ReplicationFiltervalueProviderInterface;
use Drupal\replication\Changes\Changes;

/**
 * {@inheritdoc}
 */
class ResolvedChanges extends Changes {

  /**
   * The sequence index.
   *
   * @todo: Keep the old one here add the new one as separate service??
   *
   * @var \Drupal\multiversion_sequence_filter\FilteredSequenceIndex
   */
  protected $sequenceIndex;

  /**
   * {@inheritdoc}
   */
  public function getNormal() {
    $filter = $this->getFilter();

    if (!$filter instanceof ReplicationFiltervalueProviderInterface) {
      return parent::getNormal();
    }

    $sequences = $this->sequenceIndex
      ->useWorkspace($this->workspaceId)
      ->addTypeCondition($filter->getConfiguredEntityTypeFilter())
      ->addFilterValuesCondition($filter->getConfiguredFilterValues())
      ->getRange($this->since, $this->stop, TRUE, $this->limit);

    // Removes sequences that shouldn't be processed.
    $sequences = $this->preFilterSequences($sequences, $this->since);

    // We build the change records for the sequences.
    $changes = [];
    foreach ($sequences as $sequence) {
      $uuid = $sequence['entity_uuid'];
      $changes[$uuid] = $this->buildChangeRecord($sequence);
    }

    // Now when we have rebuilt the result array we need to ensure that the
    // results array is still sorted on the sequence key, as in the index.
    $return = array_values($changes);
    usort($return, function ($a, $b) {
      return $a['seq'] - $b['seq'];
    });

    return $return;
  }

  /**
   * {@inheritdoc}
   *
   * Overridden to obey $this->includeDocs.
   */
  protected function buildChangeRecord($sequence) {
    $uuid = $sequence['entity_uuid'];
    $change_record = [
      'changes' => [
        ['rev' => $sequence['rev']],
      ],
      'id' => $uuid,
      'seq' => $sequence['seq'],
    ];
    if ($sequence['deleted']) {
      $change_record['deleted'] = TRUE;
    }
    // Include the document, but only if needed.
    if ($this->includeDocs) {
      $change_record['doc'] = $this->serializer->normalize($sequence['revision']);
    }
    return $change_record;
  }

}
