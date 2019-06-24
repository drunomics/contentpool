<?php

namespace Drupal\contentpool_custom_elements;

use drunomics\ServiceUtils\Core\Render\RendererTrait;
use drunomics\ServiceUtils\Symfony\HttpFoundation\RequestStackTrait;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\custom_elements\CustomElementGeneratorTrait;

/**
 * Computes a field item list for the teaser field.
 *
 * @see contentpool_custom_elements_entity_bundle_field_info()
 */
class CustomElementTeaserFeldItemList extends FieldItemList {

  use CustomElementGeneratorTrait;
  use ComputedItemListTrait;
  use RendererTrait;
  use RequestStackTrait;

  /**
   * Computes the values for an item list.
   */
  protected function computeValue() {
    $node = $this->getEntity();
    $this->list = [];
    $build['#theme'] = 'custom_element';
    $build['#entity_type_id'] = $node->getEntityTypeId();
    $build['#' . $node->getEntityTypeId()] = $node;
    $build['#view_mode'] = 'teaser';
    $build['#custom_element'] = $this->getCustomElementGenerator()
      ->generate($node, 'teaser');

    $markup = (string) $this->getrenderer()->renderPlain($build);
    // Make sure URLs to images etc. are all absolute, as in RSS feeds.
    $markup = Html::transformRootRelativeUrlsToAbsolute($markup, $this->getCurrentRequest()->getSchemeAndHttpHost());

    $this->list = [0 => $this->createItem(0, ['markup' => $markup])];
  }

}
