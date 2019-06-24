<?php

namespace Drupal\contentpool_custom_elements;

use drunomics\ServiceUtils\Core\Render\RendererTrait;
use Drupal\Core\Cache\CacheableResponse;

/**
 * Controller class.
 */
class ContentpoolCustomElementsController {

  use RendererTrait;

  /**
   * Gets custom elements scripts.
   *
   * @return \Drupal\Core\Cache\CacheableResponse
   *   The response.
   */
  public function getScripts() {
    /** @var \Drupal\contentpool_custom_elements\Service\AssetsExtractor $assets_extractor */
    $assets_extractor = \Drupal::service('contentpool_custom_elements.assets_extractor');
    $build = $assets_extractor->getRenderedAssetsFromLibraries(['custom_elements/main']);
    $markup = (string) $this->getrenderer()->renderPlain($build);

    // Update the Response object now that the placeholders have been rendered.
    $response = new CacheableResponse();
    $response->setContent($markup);
    return $response;
  }

}
