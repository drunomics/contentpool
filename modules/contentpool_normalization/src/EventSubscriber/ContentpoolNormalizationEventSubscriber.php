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
   * @param \Drupal\replication\Event\ReplicationContentDataAlterEvent $event
   *   The event.
   */
  public function onAlterContentData(ReplicationContentDataAlterEvent $event) {
    // Add some data under a '_test' key.
    $normalized = $event->getData();

    $language_keys = array_filter(array_keys($normalized), function ($value) {
      return !in_array($value{0}, ['_', '@']);
    });

    $entity = $event->getEntity();
    if ($entity->getEntityTypeId() == 'taxonomy_term') {
      foreach ($language_keys as $key) {
        // Remove status for taxonomy terms provided.
        unset($normalized[$key]['status']);
        unset($normalized[$key]['field_paragraphs']);
      }
    }

    if ($entity->getEntityTypeId() == 'node' && $entity->bundle() == 'article') {
      foreach ($language_keys as $key) {
        $paragraph_data = [];
        foreach ($entity->field_paragraphs->getValue() as $delta => $value) {
          $paragraph_data[$delta] = $entity->field_paragraphs->get($delta)->entity->toArray();
        }
        $normalized[$key]['field_data'][] = $paragraph_data;
        // @todo: Handle paragraph via custom elements.
        unset($normalized[$key]['field_paragraphs']);
      }
    }

    // Remove path alias information from all entities.
    foreach ($language_keys as $key) {
      unset($normalized[$key]['path']);
    }

    $event->setData($normalized);
  }

}
