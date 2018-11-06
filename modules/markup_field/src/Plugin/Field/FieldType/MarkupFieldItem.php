<?php

namespace Drupal\markup_field\Plugin\Field\FieldType;

use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\LibraryDependencyResolverInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\State\State;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Defines the 'markup' field type.
 *
 * @FieldType(
 *   id = "markup_field",
 *   label = @Translation("Markup field"),
 *   description = @Translation("Rendered output."),
 *   default_formatter = "rendered_markup"
 * )
 */
class MarkupFieldItem extends FieldItemBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The library dependency resolver.
   *
   * @var \Drupal\Core\Asset\LibraryDependencyResolverInterface
   */
  protected $libraryDependencyResolver;

  /**
   * The JS asset collection renderer service.
   *
   * @var \Drupal\Core\Asset\AssetCollectionRendererInterface
   */
  protected $jsCollectionRenderer;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct($definition, $name, TraversableTypedDataInterface $parent, EntityFieldManagerInterface $entity_field_manager, RendererInterface $renderer, LibraryDiscoveryInterface $library_discovery, LibraryDependencyResolverInterface $library_dependency_resolver, AssetCollectionRendererInterface $js_collection_renderer, State $state) {
    parent::__construct($definition, $name, $parent);
    $this->entityFieldManager = $entity_field_manager;
    $this->renderer = $renderer;
    $this->libraryDiscovery = $library_discovery;
    $this->libraryDependencyResolver = $library_dependency_resolver;
    $this->jsCollectionRenderer = $js_collection_renderer;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL) {
    return new static(
      $definition,
      $name,
      $parent,
      \Drupal::service('entity_field.manager'),
      \Drupal::service('renderer'),
      \Drupal::service('library.discovery'),
      \Drupal::service('library.dependency_resolver'),
      \Drupal::service('asset.js.collection_renderer'),
      \Drupal::service('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Field markup'))
      ->setComputed(TRUE);
    $properties['assets'] = MapDataDefinition::create()
      ->setLabel(t('Assets'))
      ->setComputed(TRUE);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'description' => 'Field markup.',
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
        ],
        'assets' => [
          'description' => 'Assets required to properly render markup.',
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
          'serialize' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'field' => [],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Choose the field which will be used for generating a mark up.
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);
    /** @var \Drupal\field_ui\Form\FieldConfigEditForm $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\field\Entity\FieldConfig $entity */
    $entity = $form_object->getEntity();
    $entity_type = $entity->get('entity_type');
    $bundle = $entity->get('bundle');
    // List the fields available for current entity.
    $options = [];
    $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    foreach ($fieldDefinitions as $field => $fieldDefinition) {
      if ($fieldDefinition instanceof FieldConfigInterface) {
        $options[$field] = $field;
      }
    }
    // Add dropdown so the field is selectable.
    $element['field'] = [
      '#type' => 'select',
      '#title' => t('Field to be rendered'),
      '#options' => $options,
      '#default_value' => $this->getSetting('field'),
    ];
    return $element;
  }

  /**
   * Get assets.
   *
   * @return array
   *   Assets build.
   */
  public function getAssets() {
    $javascript = [];
    $libraries_to_load = $this->libraryDependencyResolver
      ->getLibrariesWithDependencies(['custom_elements/main']);

    foreach ($libraries_to_load as $library) {
      list($extension, $name) = explode('/', $library, 2);
      $definition = $this->libraryDiscovery->getLibraryByName($extension, $name);
      foreach ($definition['js'] as $options) {
        $options += [
          'type' => 'file',
          'group' => JS_DEFAULT,
          'weight' => 0,
          'cache' => TRUE,
          'preprocess' => TRUE,
          'attributes' => [],
          'version' => NULL,
          'browsers' => [],
        ];

        // Make sure js files get absolute URLs by treating them as external
        // URLs.
        if ($options['type'] == 'file') {
          $default_query_string = $this->state->get('system.css_js_query_string') ?: '0';
          $query_string = $options['version'] == -1 ? $default_query_string : 'v=' . $options['version'];
          $query_string_separator = (strpos($options['data'], '?') !== FALSE) ? '&' : '?';
          $options['data'] = file_create_url($options['data']);
          $options['data'] .= $query_string_separator . ($options['cache'] ? $query_string : REQUEST_TIME);
          $options['type'] = 'external';
        }

        // Always add a tiny value to the weight, to conserve the insertion
        // order.
        $options['weight'] += count($definition['js']) / 1000;

        // Local and external files must keep their name as the associative
        // key so the same JavaScript file is not added twice.
        $javascript[$options['data']] = $options;
      }
    }
    return $this->jsCollectionRenderer->render($javascript);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    if (empty($this->locked)) {
      // Prepare field build.
      $field_build = [];
      $entity = $this->getEntity();
      $field = $this->getSetting('field');
      if ($field && isset($entity->{$field}) && $entity->{$field}->getValue()) {
        $field_build = $entity->{$field}->view('node.full');
      }
      $this->value = (string) $this->renderer->renderPlain($field_build);
      // Prepare assets.
      // Attach assets. For now just, append them.
      // Also see \Drupal\Core\Asset\AssetResolver::getJsAssets()
      $assets_build = ['assets' => $this->getAssets()];
      $this->assets = (string) $this->renderer->renderPlain($assets_build);
    }
    parent::preSave();
  }

  /**
   * Get field markup.
   *
   * @return array
   *   Field markup.
   */
  public function getMarkupValue() {
    return $this->value ?? [];
  }

  /**
   * Get assets value.
   *
   * @return array
   *   Assets value.
   */
  public function getAssetsValue() {
    return $this->assets ?? [];
  }

}
