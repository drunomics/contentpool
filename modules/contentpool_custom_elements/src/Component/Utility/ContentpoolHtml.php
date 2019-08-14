<?php

namespace Drupal\contentpool_custom_elements\Component\Utility;

use Drupal\Component\Utility\Html;

/**
 * Provides DOMDocument helpers for parsing and serializing HTML strings.
 *
 * @ingroup utility
 */
class ContentpoolHtml extends Html {

  /**
   * {@inheritdoc}
   */
  protected static $uriAttributes = [
    'poster',
    'src',
    'cite',
    'data',
    'action',
    'formaction',
    'srcset',
    'about',
  ];

}
