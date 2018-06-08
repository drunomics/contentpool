<?php

namespace Drupal\contentpool_remote_register;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Time record entities.
 *
 * @ingroup remote_registration
 */
class RemoteRegistrationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Time record ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\ems_time_records\Entity\TimeRecord */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.remote_registration.canonical',
      ['remote_registration' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
