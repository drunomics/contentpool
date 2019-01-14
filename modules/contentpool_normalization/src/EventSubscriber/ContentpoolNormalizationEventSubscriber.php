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
        unset($normalized[$key]['field_remote_site']);
      }
    }

    if ($entity->getEntityTypeId() == 'node' && $entity->bundle() == 'article') {
      foreach ($language_keys as $key) {
        // Paragraphs are handled via custom elements and the markup field.
        unset($normalized[$key]['field_paragraphs']);
        // Also add the paragraphs field-data to the data field.
        $paragraph_data = [];
        foreach ($entity->field_paragraphs->getValue() as $delta => $value) {
          $paragraph_data[$delta] = $entity->field_paragraphs->get($delta)->entity->toArray();
        }
        $normalized[$key]['field_data'][] = ['value' => json_encode($paragraph_data)];
      }
    }

    foreach ($language_keys as $key) {
      // Remove path alias information from all entities.
      unset($normalized[$key]['path']);
      // Do not replicate moderation_state - only the status flag.
      unset($normalized[$key]['moderation_state']);
      unset($normalized[$key]['publish_state']);
      unset($normalized[$key]['unpublish_state']);
    }

    $event->setData($normalized);
  }

}
