<?php

namespace Drupal\markup_field\Normalizer;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\markup_field\Plugin\Field\FieldType\MarkupFieldItem;
use Drupal\hal\Normalizer\FieldItemNormalizer;

/**
 * Converts values for MarkupFieldItem to and from common formats for hal.
 */
class MarkupFieldItemNormalizer extends FieldItemNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $format = ['hal_json', 'json'];

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = MarkupFieldItem::class;

  /**
   * {@inheritdoc}
   */
  protected function normalizedFieldValues(FieldItemInterface $field_item, $format, array $context) {
    return $field_item->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    if (empty($data)) {
      return NULL;
    }
    // For some reason the data are wrapped to array with the field name as
    // array key. Get the first data item to get the real field values.
    $field_values = reset($data);
    /** @var \Drupal\markup_field\Plugin\Field\FieldType\MarkupFieldItem $field */
    $markup_field = parent::denormalize($data, $class, $format, $context);
    $markup_field->setValue([]);
    $markup_field->value = $field_values[0]['value'] ?? '';
    $markup_field->assets = $field_values[0]['assets'] ?? '';
    $markup_field->locked = TRUE;
    return $markup_field;
  }

}
