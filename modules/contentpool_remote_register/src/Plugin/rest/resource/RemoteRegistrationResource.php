<?php

namespace Drupal\contentpool_remote_register\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\relaxed\SensitiveDataTransformer;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * The remote registration resource plugin.
 *
 * @RestResource(
 *   id = "contentpool:remote_registration",
 *   label = "Remote registrations",
 *   uri_paths = {
 *     "create" = "/api/remote-registration",
 *   }
 * )
 */
class RemoteRegistrationResource extends ResourceBase {

  /**
   * The sensitive data transformer.
   *
   * @var \Drupal\relaxed\SensitiveDataTransformer
   */
  protected $sensitiveDataTransformer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * RemoteRegistrationResource constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param array $serializer_formats
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\relaxed\SensitiveDataTransformer $sensitive_data_transformer
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, SensitiveDataTransformer $sensitive_data_transformer, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->sensitiveDataTransformer = $sensitive_data_transformer;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('relaxed.sensitive_data.transformer'),
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Provides a response to post for the endpoint.
   *
   * @param $data
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function post($data) {
    // Create new remote registration.
    $entity_storage = $this->entityTypeManager->getStorage('remote_registration');
    $remote_registrations = $entity_storage->loadByProperties([
      'site_uuid' => $data['site_uuid']
    ]);

    // Create new remote registration if none exists.
    if (empty($remote_registrations)) {
      // We create an encoded uri for this site.
      $encoded_uri = $this->sensitiveDataTransformer->set($data['endpoint_uri']);

      /** @var \Drupal\Core\Entity\Entity $remote_registration */
      $remote_registration = $entity_storage->create([
        'site_uuid' => $data['site_uuid'],
        'name' => $data['site_name'],
        'url' => $data['site_domain'],
        'endpoint_uri' => $encoded_uri,
      ]);

      $remote_registration->save();
      $status_code = 201;
    }
    else {
      $remote_registration = reset($remote_registrations);
      $status_code = 200;
    }

    return new ResourceResponse(
      [
        'site_uuid' => $this->configFactory->get('system.site')->get('uuid')
      ],
      $status_code
    );
  }

}
