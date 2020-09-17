<?php

namespace Drupal\contentpool_remote_register\Plugin\rest\resource;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A rest resource that provides term reference fields for satellites.
 *
 * @RestResource(
 *   id = "contentpool:contentpool_term_reference_fields",
 *   label = "Contentpool term reference fields",
 *   uri_paths = {
 *     "canonical" = "/api/contentpool-term-reference-fields",
 *   }
 * )
 */
class ContentpoolTermReferenceFieldsResource extends ResourceBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * RemoteRegistrationResource constructor.
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param array $serializer_formats
   *   The array of serializer formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
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
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Provides a response for the endpoint.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   Response.
   */
  public function get(Request $request) {
    $entity_type_id = $request->query->get('entity_type_id');
    $bundle = $request->query->get('bundle');
    $response_data = [];

    try {
      $field_definitions = $this->entityFieldManager
        ->getFieldDefinitions($entity_type_id, $bundle);
    } catch (PluginNotFoundException $e) {
      $message = $this->t('Plugin not found for entity_type_id: @entity_type_id and bundle: @bundle', [
        '@entity_type_id' => $entity_type_id,
        '@bundle' => $bundle,
      ]);
      return new ModifiedResourceResponse($message, 404);
    }

    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $term_reference_fields */
    $term_reference_fields = array_filter($field_definitions, function (FieldDefinitionInterface $field_definition) {
      /** @var \Drupal\field\FieldStorageConfigInterface $storage_config */
      $storage_config = $field_definition->getFieldStorageDefinition();
      return $storage_config->getType() == 'entity_reference' && $storage_config->getSetting('target_type') == 'taxonomy_term';
    });

    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    foreach ($term_reference_fields as $field_definition) {
      $taxonomy_id = reset($field_definition->getSetting('handler_settings')['target_bundles']);
      $term_tree = $term_storage->loadTree($taxonomy_id, 0, NULL, TRUE);
      $response_data[$field_definition->getName()] = [
        'id' => $taxonomy_id,
        'field' => $field_definition->getName(),
        'label' => $field_definition->getLabel(),
        'terms' => static::buildResponseTree($term_tree),
      ];
    }

    return new ModifiedResourceResponse($response_data, 200);
  }

  /**
   * Build a tree of taxonomy terms data out of a flat list.
   *
   * @param \Drupal\taxonomy\TermInterface[] $terms
   *   Flat list of terms.
   * @param int $parent
   *   Parent id.
   *
   * @return mixed[]
   *   A nested array of terms keyed by uuid, containing label & child terms.
   */
  protected static function buildResponseTree(array $terms, $parent = 0) {
    $tree = [];
    $id_map = [];
    foreach ($terms as $key => $term) {
      $id_map[$term->uuid()] = $term->id();
      $parent_id = reset($term->parents);
      if ($parent_id == $parent) {
        $tree[$term->uuid()] = [
          'id' => $term->uuid(),
          'label' => $term->label(),
        ];
        // Term is not used anymore, so reduce iterations in the recursive call.
        unset($terms[$key]);
      }
    }

    foreach ($tree as $uuid => &$tree_node) {
      $children = static::buildResponseTree($terms, $id_map[$uuid]);
      if (!empty($children)) {
        $tree_node['children'] = $children;
      }
    }

    return $tree;
  }

}
