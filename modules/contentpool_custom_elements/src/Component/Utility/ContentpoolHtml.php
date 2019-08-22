<?php

namespace Drupal\contentpool_custom_elements\Component\Utility;

use Drupal\Component\Utility\Html;
use Drupal\Core\Site\Settings;

/**
 * Provides DOMDocument helpers for parsing and serializing HTML strings.
 *
 * @see Drupal\Component\Utility\Html
 */
class ContentpoolHtml {

  /**
   * {@inheritdoc}
   *
   * Only cover urls pointing to files.
   */
  protected static $uriAttributes = [
    'poster',
    'src',
  ];

  /**
   * Gets the base URL to use for files.
   *
   * @return string
   *   The base URL with a trailing slash.
   */
  public static function getFilesBaseUrl() {
    return Settings::get('file_public_base_url') ?: \Drupal::request()->getSchemeAndHttpHost();
  }

  /**
   * Converts root-relative image src URLs to absolute URLs.
   *
   * @param string $html
   *   The partial (X)HTML snippet to load. Invalid markup will be corrected on
   *   import.
   * @param string $base_url
   *   (optional) The base URL to add.
   *
   * @return string
   *   The updated (X)HTML snippet.
   */
  public static function transformRootRelativeUrlsToAbsolute($html, $base_url = NULL) {
    $html_dom = Html::load($html);
    $base_url = $base_url ?: static::getFilesBaseUrl();
    $xpath = new \DOMXpath($html_dom);

    // Update all root-relative URLs to absolute URLs in the given HTML.
    foreach (static::$uriAttributes as $attr) {
      foreach ($xpath->query("//*[starts-with(@$attr, '/') and not(starts-with(@$attr, 'http'))]") as $node) {
        $node->setAttribute($attr, $base_url . $node->getAttribute($attr));
      }
      foreach ($xpath->query("//*[@srcset]") as $node) {
        // @see https://html.spec.whatwg.org/multipage/embedded-content.html#attr-img-srcset
        // @see https://html.spec.whatwg.org/multipage/embedded-content.html#image-candidate-string
        $image_candidate_strings = explode(',', $node->getAttribute('srcset'));
        $image_candidate_strings = array_map('trim', $image_candidate_strings);
        for ($i = 0; $i < count($image_candidate_strings); $i++) {
          $image_candidate_string = $image_candidate_strings[$i];
          if ($image_candidate_string[0] === '/' && strpos($image_candidate_string, 'http') !== 0) {
            $image_candidate_strings[$i] = $base_url . $image_candidate_string;
          }
        }
        $node->setAttribute('srcset', implode(', ', $image_candidate_strings));
      }
    }
    return Html::serialize($html_dom);
  }

}
