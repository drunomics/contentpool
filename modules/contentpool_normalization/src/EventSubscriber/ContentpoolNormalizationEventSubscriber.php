<?php

namespace Drupal\contentpool_normalization\EventSubscriber;

use Drupal\replication\Event\ReplicationContentDataAlterEvent;
use Drupal\replication\Event\ReplicationDataEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for content normalization events.
 */
class ContentpoolNormalizationEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[ReplicationDataEvents::ALTER_CONTENT_DATA][] = ['onAlterContentData', 0];
    return $events;
  }

  /**
   * Alter content normalization data.
   *
   * @param ReplicationContentDataAlterEvent $event
   */
  public function onAlterContentData(ReplicationContentDataAlterEvent $event) {
    // Add some data under a '_test' key.
    $normalized = $event->getData();

    $entity = $event->getEntity();
    if ($entity->getEntityTypeId() == 'taxonomy_term') {
      foreach ($normalized as $key => $translation) {
        // Skip any keys that start with '_' or '@'.
        if (in_array($key{0}, ['_', '@'])) {
          continue;
        }

        // Remove status for taxonomy terms provided
        unset($normalized[$key]['status']);
      }
    }

    $event->setData($normalized);
  }

}
