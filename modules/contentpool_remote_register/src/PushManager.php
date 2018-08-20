<?php

namespace Drupal\contentpool_remote_register;

use Drupal\contentpool_remote_register\Entity\RemoteRegistration;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\relaxed\SensitiveDataTransformer;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Serializer\Serializer;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DestructableInterface;

/**
 * Pushes content to remotes by triggering pulls.
 */
class PushManager implements PushManagerInterface, DestructableInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The Guzzle HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The sensitive data transformer.
   *
   * @var \Drupal\relaxed\SensitiveDataTransformer
   */
  protected $sensitiveDataTransformer;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Array of remote registrations to pull.
   *
   * @var array
   */
  protected $pullRegistrations = [];

  /**
   * Constructs a PushManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The http client.
   * @param \Drupal\relaxed\SensitiveDataTransformer $sensitive_data_transformer
   *   The data transformer.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Serializer $serializer, ClientInterface $http_client, SensitiveDataTransformer $sensitive_data_transformer, MessengerInterface $messenger, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->httpClient = $http_client;
    $this->sensitiveDataTransformer = $sensitive_data_transformer;
    $this->messenger = $messenger;
    $this->serializer = $serializer;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function pushToRegisteredRemotes() {
    $remote_registrations = $this->entityTypeManager->getStorage('remote_registration')->loadMultiple();

    $counter = 0;
    foreach ($remote_registrations as $remote_registration) {
      // We try to initialize a pull from the remote.
      $this->triggerPullAtRemote($remote_registration);
      $counter++;
    }

    return $counter;
  }

  /**
   * {@inheritdoc}
   */
  public function triggerPullAtRemote(RemoteRegistration $remote_registration) {
    $this->pullRegistrations[] = $remote_registration;

    // For CLI invokations run the trigger now. Others run on destruction.
    // @see ::destruct().
    if (php_sapi_name() == 'cli') {
      $this->doTrigger();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    $this->doTrigger();
  }

  /**
   * Actually runs all queued triggers.
   */
  protected function doTrigger() {
    foreach ($this->pullRegistrations as $index => $remote_registration) {
      $this->doTriggerPullAtRemote($remote_registration);
      // Ensure processing only happens once.
      unset($this->pullRegistrations[$index]);
    }
  }

  /**
   * We process the pull initialization at the remote.
   *
   * @param \Drupal\contentpool_remote_register\Entity\RemoteRegistration $remote_registration
   *   The remote registration entity.
   */
  protected function doTriggerPullAtRemote(RemoteRegistration $remote_registration) {
    $encoded_uri = $remote_registration->getEndpointUri();
    $url = $this->sensitiveDataTransformer->get($encoded_uri);
    $url_parts = parse_url($url);

    $credentials = '';
    if (isset($url_parts['user']) && isset($url_parts['pass'])) {
      $credentials = $url_parts['user'] . ':' . $url_parts['pass'] . '@';
    }

    $base_url = $url_parts['scheme'] . '://' . $credentials . $url_parts['host'];

    if ($url_parts['scheme'] != 'https') {
      $this->messenger->addWarning($this->t('Warning: Insecure connection used for remote @name.', ['@name' => $remote_registration->getName()]));
    }

    try {
      $response = $this->httpClient->post($base_url . '/api/trigger-pull?_format=json', $this->generatePullPayload());

      if ($response->getStatusCode() === 200) {
        $this->result = TRUE;
        $this->message = $this->t('Successfully triggered pull at remote @name.', ['@name' => $remote_registration->getName()]);
      }
      else {
        $this->message = $this->t('Remote @name returns status code @status when triggering pull.', [
          '@status' => $response->getStatusCode(),
          '@name' => $remote_registration->getName(),
        ]);
      }
    }
    catch (\Exception $e) {
      $this->message = $e->getMessage();
      watchdog_exception('contentpool', $e);
    }
  }

  /**
   * Helper function that builds the data for the request.
   *
   * @return array
   *   The payload.
   */
  protected function generatePullPayload() {
    $body = [
      'site_uuid' => $this->configFactory->get('system.site')->get('uuid'),
    ];

    return [
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/json',
      ],
      RequestOptions::BODY => $this->serializer->serialize($body, 'json'),
    ];
  }

}
