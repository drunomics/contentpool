<?php

namespace Drupal\contentpool_remote_info\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @RestResource(
 *   id = "contentpool:contentpool_channels",
 *   label = "Contentpool channels",
 *   uri_paths = {
 *     "canonical" = "/_contentpool-channels",
 *   }
 * )
 */
class ContentpoolChannelsResource extends ResourceBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * Provides a response to post for the endpoint.
   *
   * @param $data
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   */
  public function get($data) {
    // Load all taxonomy terms from channel vocabulary.
    $channels = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('channel', 0, NULL, TRUE);

    $channel_options = [];
    /** @var \Drupal\Core\Entity\ContentEntityInterface $channel */
    foreach ($channels as $channel) {
      $channel_options[$channel->uuid()] = $channel->label();
    }

    return new ModifiedResourceResponse(
      [
        'contentpool_channels' => $channel_options,
      ],
      200
    );
  }

}
