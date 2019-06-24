<?php

namespace Drupal\contentpool_custom_elements;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Computes a field item list for the teaser field.
 *
 * @see contentpool_custom_elements_entity_bundle_field_info()
 */
class CustomElementTeaserFeldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * Computes the values for an item list.
   */
  protected function computeValue() {
    $new_list = [];
    $new_list[0] = $this->createItem(0, ['markup' => '<p>test</p>']);
    $this->list = $new_list;
  }

}
