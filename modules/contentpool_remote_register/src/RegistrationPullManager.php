<?php

namespace Drupal\contentpool_remote_register;

use Drupal\contentpool_remote_register\Entity\RemoteRegistration;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\multiversion\Workspace\ConflictTrackerInterface;
use Drupal\relaxed\SensitiveDataTransformer;
use Drupal\workspace\Entity\WorkspacePointer;
use Drupal\workspace\ReplicatorInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Serializer\Serializer;

/**
 * Helper class to get training references and backreferences.
 */
class RegistrationPullManager implements RegistrationPullManagerInterface {

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
   * Constructs a RemoteAutopullManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Serializer $serializer, ClientInterface $http_client, SensitiveDataTransformer $sensitive_data_transformer, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->httpClient = $http_client;
    $this->sensitiveDataTransformer = $sensitive_data_transformer;
    $this->messenger = $messenger;
    $this->serializer = $serializer;
  }

  /**
   * @inheritdoc
   */
  public function pullFromRemoteRegistrations() {
    $remote_registrations = $this->entityTypeManager->getStorage('remote_registration')->loadMultiple();

    $counter = 0;
    foreach ($remote_registrations as $remote_registration) {
      // We try to do a pull from the remote.
      $this->initRemotePull($remote_registration);
      $counter++;
    }

    return $counter;
  }

  /**
   * {@inheritdoc}
   */
  public function initRemotePull(RemoteRegistration $remote_registration) {
    $encoded_uri = $remote_registration->getEndpointUri();
    $url = $this->sensitiveDataTransformer->get($encoded_uri);
    $url_parts = parse_url($url);

    $credentials = '';
    if (isset($url_parts['user']) && isset($url_parts['pass'])) {
      $credentials = $url_parts['user'] . ':' . $url_parts['pass'] . '@';
    }

    $base_url = $url_parts['scheme'] . '://' . $credentials . $url_parts['host'];

    if ($url_parts['scheme'] != 'https') {
      $this->messenger->addWarning($this->t('Warning: Insecure connection used for remote.'));
    }

    try {
      $response = $this->httpClient->get($base_url . '/_init-pull?_format=json', $this->generatePullPayload());

      if ($response->getStatusCode() === 200) {
        $this->result = TRUE;
        $this->message = $this->t('Successfully initiated pull.');
      }
      else {
        $this->message = $this->t('Remote returns status code @status.', ['@status' => $response->getStatusCode()]);
      }
    }
    catch (\Exception $e) {
      $this->message = $e->getMessage();
      watchdog_exception('relaxed', $e);
    }
  }

  public function generatePullPayload() {
    $body = [
      'site_uuid' => \Drupal::config('system.site')->get('uuid'),
    ];

    $serialized_body = $this->serializer->serialize($body, 'json');

    return [
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/json',
      ],
      RequestOptions::BODY => $serialized_body,
    ];
  }

}
