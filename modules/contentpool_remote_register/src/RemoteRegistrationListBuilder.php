<?php

namespace Drupal\contentpool_remote_register;

use drunomics\ServiceUtils\Core\Entity\EntityTypeManagerTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of remote registration entities.
 */
class RemoteRegistrationListBuilder extends EntityListBuilder {

  use EntityTypeManagerTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    $header['site_uuid'] = $this->t('Site UUID');
    $header['url'] = $this->t('Url');
    $header['operations'] = $this->t('Operations');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['name'] = $entity->label();
    $row['site_uuid'] = $entity->getSiteUuid();

    $url = Url::fromUri($entity->getUrl());
    $row['url'] = Link::fromTextAndUrl($entity->getUrl(), $url);

    $list_builder = $this->getEntityTypeManager()->getListBuilder('remote_registration');
    $operations = $list_builder->getOperations($entity);
    $row['operations'] = [
      'data' => [
        '#type' => 'operations',
        '#links' => $operations,
      ],
    ];

    return $row + parent::buildRow($entity);
  }

}
