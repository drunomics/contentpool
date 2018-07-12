<?php

namespace Drupal\contentpool_normalization\Normalizer;

use Drupal\replication\Normalizer\PathFieldItemListNormalizer as OriginalPathFieldItemListNormalizer;

class PathFieldItemListNormalizer extends OriginalPathFieldItemListNormalizer {
  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    return [];
  }

}