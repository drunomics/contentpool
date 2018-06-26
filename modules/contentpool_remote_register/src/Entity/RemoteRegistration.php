<?php

namespace Drupal\contentpool_remote_register\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\relaxed\Entity\Remote;
use GuzzleHttp\Psr7\Uri;

/**
 * Defines the Remote registration entity.
 *
 * @ingroup remote_registration
 *
 * @ContentEntityType(
 *   id = "remote_registration",
 *   label = @Translation("Remote registration"),
 *   handlers = {
 *     "list_builder" = "Drupal\contentpool_remote_register\RemoteRegistrationListBuilder",
 *     "views_data" = "Drupal\contentpool_remote_register\Entity\RemoteRegistrationViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *    "form" = {
 *       "delete" = "Drupal\contentpool_remote_register\Form\RemoteRegistrationDeleteForm",
 *     },
 *   },
 *   base_table = "remote_registration",
 *   admin_permission = "administer remote registrations",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/remote-registrations/{remote_registration}",
 *     "collection" = "/admin/config/remote-registrations",
 *     "delete-form" = "/admin/config/remote-registrations/{remote_registration}/delete",
 *   },
 *   field_ui_base_route = "entity.remote_registration.collection"
 * )
 */
class RemoteRegistration extends ContentEntityBase implements RemoteRegistrationInterface {

  use EntityChangedTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return $this->get('url')->value;
  }

  public function getSiteUUID() {
    return $this->get('site_uuid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setUrl($url) {
    $this->set('url', $url);
    return $this;
  }

  public function getEndpointUri() {
    return $this->get('endpoint_uri')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  public function getRemote() {
    return $this->remote_id->entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the remote site.'))
      ->setSettings([
        'max_length' => 255
      ])
      ->setRequired(TRUE);

    $fields['url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Url'))
      ->setDescription(t('The url of the remote site.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setRequired(TRUE);

    $fields['endpoint_uri'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Relaxed Endpoint URI'))
      ->setDescription(t('The endpoint of the relaxed module api.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setRequired(TRUE);

    $fields['site_uuid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Site UUID'))
      ->setDescription(t('The uuid of the remote site.'))
      ->setSettings([
        'max_length' => 255
      ])
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['remote_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Remote'))
      ->setDescription(t('The remote.'))
      ->setSetting('target_type', 'remote')
      ->setReadOnly(TRUE);

    return $fields;
  }

  /**
   * @inheritdoc
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Create remote entites for this registration entity.
    $remote = $this->getRemote();

    if (!$remote) {
      $remote = Remote::create([
        'id' => $this->getSiteUUID(),
        'label' => $this->getName(),
        'uri' => $this->getEndpointUri()
      ]);
    }
    else {
      $remote->set('name', $this->getName());
      $remote->set('uri', $this->getEndpointUri());
    }

    $remote_status = $remote->save();

    switch ($remote_status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Remote.', [
          '%label' => $remote->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Remote.', [
          '%label' => $remote->label(),
        ]));
    }

    // Update reference to the remote entity.
    $this->remote_id->target_id = $remote->id();
  }

  /**
   * @inheritdoc
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    // Delete all remotes referenced by the remote registrations.
    foreach($entities as $remote_registration) {
      if (!$remote_registration->remote_id->isEmpty()) {
        $remote_registration->remote_id->entity->delete();
      }
    }

    parent::postDelete($storage, $entities);
  }

}
