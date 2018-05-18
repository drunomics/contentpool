<?php

namespace Drupal\contentpool_remote_register\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityBase;

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
 *   },
 *   field_ui_base_route = "entity.remote_registration.collection"
 * )
 */
class RemoteRegistration extends ContentEntityBase implements RemoteRegistrationInterface {

  use EntityChangedTrait;

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

    $fields['ip'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IP address'))
      ->setDescription(t('The ip address of the remote site.'))
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

    return $fields;
  }

}
