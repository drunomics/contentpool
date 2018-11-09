<?php

namespace Drupal\contentpool_custom_elements\Service;

use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\State\State;

/**
 * Extract css and js assets from library array.
 */
class AssetsExtractor {

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The CSS asset collection renderer service.
   *
   * @var \Drupal\Core\Asset\AssetCollectionRendererInterface
   */
  protected $cssCollectionRenderer;

  /**
   * The JS asset collection renderer service.
   *
   * @var \Drupal\Core\Asset\AssetCollectionRendererInterface
   */
  protected $jsCollectionRenderer;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(LibraryDiscoveryInterface $library_discovery, AssetCollectionRendererInterface $css_collection_renderer, AssetCollectionRendererInterface $js_collection_renderer, State $state) {
    $this->libraryDiscovery = $library_discovery;
    $this->cssCollectionRenderer = $css_collection_renderer;
    $this->jsCollectionRenderer = $js_collection_renderer;
    $this->state = $state;
  }

  /**
   * Get assets.
   *
   * @param array $libraries
   *   List of libraries.
   *
   * @return array
   *   Assets build.
   */
  public function getAssetsFromLibraries(array $libraries) {
    $assets = ['css' => [], 'js' => []];
    foreach ($libraries as $library) {
      list($extension, $name) = explode('/', $library, 2);
      $definitions = $this->libraryDiscovery->getLibraryByName($extension, $name);
      foreach ($definitions as $type => $definition) {
        // Support css and js assets.
        if ($type != 'css' && $type != 'js') {
          continue;
        }
        foreach ($definition as $options) {
          $options += [
            'type' => 'file',
            'group' => ($type == 'js') ? JS_DEFAULT : CSS_AGGREGATE_DEFAULT,
            'weight' => 0,
            'cache' => TRUE,
            'preprocess' => TRUE,
            'attributes' => [],
            'version' => NULL,
            'media' => NULL,
            'browsers' => [],
          ];

          // Make sure files get absolute URLs by treating them as external
          // URLs.
          if ($options['type'] == 'file') {
            $default_query_string = $this->state->get('system.css_js_query_string') ?: '0';
            $query_string = $options['version'] == -1 ? $default_query_string : 'v=' . $options['version'];
            $query_string_separator = (strpos($options['data'], '?') !== FALSE) ? '&' : '?';
            $options['data'] = file_create_url($options['data']);
            $options['data'] .= $query_string_separator . ($options['cache'] ? $query_string : REQUEST_TIME);
            $options['type'] = 'external';
          }

          // Always add a tiny value to the weight, to conserve the insertion
          // order.
          $options['weight'] += count($definition) / 1000;

          // Local and external files must keep their name as the associative
          // key so the same JavaScript file is not added twice.
          $assets[$type][$options['data']] = $options;
        }
      }
    }
    $css_assets = $this->cssCollectionRenderer->render($assets['css']);
    $js_assets = $this->jsCollectionRenderer->render($assets['js']);
    return array_merge($css_assets, $js_assets);
  }

}
