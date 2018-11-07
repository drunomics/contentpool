<?php

namespace Drupal\markup_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Defines the 'markup' field type.
 *
 * @FieldType(
 *   id = "markup_field",
 *   label = @Translation("Markup field"),
 *   description = @Translation("Rendered output."),
 *   default_formatter = "markup_field_rendered_markup",
 *   category = @Translation("Field"),
 * )
 */
class MarkupFieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Field markup'));
    $properties['assets'] = MapDataDefinition::create()
      ->setLabel(t('Assets'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        // Rendered html markup.
        'value' => [
          'description' => 'Rendered markup.',
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
        ],
        // Assets array in the format of render array for attaching assets.
        'assets' => [
          'description' => 'Assets required by rendered markup.',
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
          'serialize' => TRUE,
        ],
      ],
    ];
  }

  /**
   * Gets rendered markup.
   *
   * @return string
   *   Rendered markup.
   */
  public function getMarkup() {
    return $this->value ?? '';
  }

  /**
   * Gets render array for attaching assets.
   *
   * @return array
   *   Render array for attaching assets.
   */
  public function getAssets() {
    return $this->assets ?? [];
  }

  /**
   * Sets rendered markup.
   *
   * @$value string
   *   Rendered markup.
   */
  public function setMarkup($markup) {
    $this->value = $markup;
  }

  /**
   * Sets render array for attaching assets.
   *
   * @return array
   *   Render array for attaching assets.
   */
  public function setAssets($assets) {
    return $this->assets = $assets;
  }

}
