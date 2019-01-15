<?php

namespace Drupal\contentpool_normalization\EventSubscriber;

use Drupal\replication\Event\ReplicationContentDataAlterEvent;
use Drupal\replication\Event\ReplicationDataEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use drunomics\ServiceUtils\Core\Entity\EntityTypeManagerTrait;

/**
 * Event subscriber for content normalization events.
 */
class ContentpoolNormalizationEventSubscriber implements EventSubscriberInterface {

  use EntityTypeManagerTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[ReplicationDataEvents::ALTER_CONTENT_DATA][] = [
      'onAlterContentData',
      0,
    ];
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
        // Paragraphs are handled via custom elements and the markup field.
        unset($normalized[$key]['field_paragraphs']);
        // Also add the paragraphs field-data to the data field.
        $paragraph_data = [];
        foreach ($entity->field_paragraphs->getValue() as $delta => $value) {
          $paragraph_data[$delta] = $entity->field_paragraphs->get($delta)->entity->toArray();
        }
        $normalized[$key]['field_data'][] = ['value' => json_encode($paragraph_data)];

        if (!$entity->get('field_channel')->isEmpty()) {
          /** @var \Drupal\taxonomy\Entity\Term $root_channel */
          $channel = $entity->field_channel->entity;
          if ($channel->hasField('field_remote_site')) {
            // Check if channel is root element of tree. If it's not, find root and assign it to channel variable.
            $ancestors = $this->entityTypeManager
              ->getStorage("taxonomy_term")
              ->loadAllParents($channel->id());
            if (!empty($ancestors)) {
              foreach ($ancestors as $ancestor) {
                if (empty($ancestor->parent->target_id)) {
                  $channel = $ancestor;
                  break;
                }
              }
            }
            if (!$channel->get('field_remote_site')->isEmpty()) {
              /** @var \Drupal\contentpool_remote_register\Entity\RemoteRegistration $remote_site */
              $remote_site = $channel->field_remote_site->entity;
              $remote_url = $remote_site->getUrl();
              $normalized[$key]['field_canonical_url'] = [
                "uri" => $remote_url . '/by_uuid/' . $entity->getEntityTypeId() . '/' . $entity->uuid(),
              ];
            }
          }
        }
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
