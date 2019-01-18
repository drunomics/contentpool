<?php

namespace Drupal\contentpool_remote_register;

use Drupal\contentpool_remote_register\Entity\RemoteRegistrationInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\relaxed\SensitiveDataTransformer;
use Drupal\replication\Plugin\ReplicationFilterManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Serializer\Serializer;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use function GuzzleHttp\Promise\settle;

/**
 * Pushes content to remotes by triggering pulls.
 */
class PushManager implements PushManagerInterface {

  use StringTranslationTrait;

  /**
   * Representing an event when push is ignored to remote.
   *
   * @var int.
   */
  const PUSH_EVENT_IGNORED = 1;

  /**
   * Representing an event when push is approved to remote.
   *
   * @var int.
   */
  const PUSH_EVENT_APPROVED = 2;

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
   * The replication filter manager.
   *
   * @var \Drupal\replication\Plugin\ReplicationFilterManagerInterface
   */
  protected $replicationFilterManager;

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
   * Drupal logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Whether logging is enabled during push to remote.
   *
   * @var bool
   */
  protected $pushLogging;

  /**
   * Whether drupal messages are enabled during push to remote.
   *
   * @var bool
   */
  protected $pushMessages;

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
   * @param \Drupal\replication\Plugin\ReplicationFilterManagerInterface $replication_filter_manager
   *   The replication filter manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The config factory service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Serializer $serializer, ClientInterface $http_client, SensitiveDataTransformer $sensitive_data_transformer, ReplicationFilterManagerInterface $replication_filter_manager, MessengerInterface $messenger, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->httpClient = $http_client;
    $this->sensitiveDataTransformer = $sensitive_data_transformer;
    $this->replicationFilterManager = $replication_filter_manager;
    $this->messenger = $messenger;
    $this->serializer = $serializer;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('contentpool_remote_register');
    $settings = $config_factory->get('contentpool_remote_register.settings');
    $this->pushLogging = $settings->get('logging_status') ? TRUE : FALSE;
    $this->pushMessages = $settings->get('messaging_status') ? TRUE : FALSE;
  }

  /**
   * Whether remote applies for given entity.
   *
   * Checks respect all replication filters set by current remote to evaluate
   * if given remote applies for replication of given entity.
   *
   * @param \Drupal\contentpool_remote_register\Entity\RemoteRegistrationInterface $remote_registration
   *   The remote registration entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   Whether remote applies to replicate current entity.
   */
  protected function remoteAppliesForEntity(RemoteRegistrationInterface $remote_registration, EntityInterface $entity) {
    $replication_filters = $remote_registration->replication_filters;
    $replication_filter_id = $replication_filters->filter_id;
    if (!$replication_filter_id) {
      return FALSE;
    }
    $replication_parameters = $replication_filters->parameters;
    if (!$replication_parameters) {
      return FALSE;
    }
    $replication_filter = $this->replicationFilterManager->createInstance($replication_filter_id, $replication_parameters);
    return $replication_filter->filter($entity);
  }

  /**
   * Log an push event.
   *
   * @param \Drupal\contentpool_remote_register\Entity\RemoteRegistrationInterface $remote_registration
   *   The remote registration entity.
   * @param int $event_type
   *   The event type (either approved or ignored).
   */
  protected function logPushEvent(RemoteRegistrationInterface $remote_registration, $event_type) {
    if ($this->pushLogging || $this->pushMessages) {
      switch ($event_type) {
        case self::PUSH_EVENT_IGNORED:
          $message = $this->t('Ignored push to remote @name.', ['@name' => $remote_registration->getName()]);
          break;

        case self::PUSH_EVENT_APPROVED:
          $message = $this->t('Successfully triggered push to remote @name.', ['@name' => $remote_registration->getName()]);
          break;
      }
      if ($this->pushLogging) {
        $this->logger->info($message);
      }
      if ($this->pushMessages) {
        $this->messenger->addMessage($message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function pushToRegisteredRemotes(EntityInterface $entity = NULL) {
    /** @var \Drupal\contentpool_remote_register\Entity\RemoteRegistration[] $remote_registrations */
    $remote_registrations = $this->entityTypeManager->getStorage('remote_registration')->loadByProperties(['push_notifications' => TRUE]);

    $counter = 0;
    /** @var \GuzzleHttp\Promise\PromiseInterface[] $promises */
    $promises = [];

    // Initialize concurrent requests to pull at the remotes.
    foreach ($remote_registrations as $remote_registration) {
      // If entity is given, evaluate pull only if remote applies to replicate
      // given entity.
      if ($entity) {
        // If remote does not apply for entity, do not trigger pull.
        if (!$this->remoteAppliesForEntity($remote_registration, $entity)) {
          // Log an event if configured.
          $this->logPushEvent($remote_registration, self::PUSH_EVENT_IGNORED);
          continue;
        }
      }
      $promises[] = $this->triggerPullAtRemote($remote_registration, TRUE);
      $counter++;
      // Log an event if configured.
      $this->logPushEvent($remote_registration, self::PUSH_EVENT_APPROVED);
    }

    // To wait for the requests to complete, even if some of them fail.
    settle($promises)->wait();

    return $counter;
  }

  /**
   * {@inheritdoc}
   */
  public function triggerPullAtRemote(RemoteRegistrationInterface $remote_registration, $asynchronous = FALSE) {
    $promise = $this->doTriggerPullAtRemote($remote_registration);

    if (!$asynchronous) {
      $promise->wait();
    }

    return $promise;
  }

  /**
   * We process the pull initialization at the remote.
   *
   * @param \Drupal\contentpool_remote_register\Entity\RemoteRegistrationInterface $remote_registration
   *   The remote registration entity.
   *
   * @return \GuzzleHttp\Promise\PromiseInterface
   *   Promise for the asynchronous pull trigger request.
   */
  protected function doTriggerPullAtRemote(RemoteRegistrationInterface $remote_registration) {
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

    $promise = $this->httpClient->postAsync($base_url . '/api/trigger-pull?_format=json', $this->generatePullPayload())
      ->then(
        function (ResponseInterface $response) use ($remote_registration) {
          if ($response->getStatusCode() === 200) {
            $message = $this->t('Successfully triggered pull at remote @name.', ['@name' => $remote_registration->getName()]);
            $this->logger->info($message);
          }
          else {
            $message = $this->t('Remote @name returns status code @status when triggering pull.', [
              '@status' => $response->getStatusCode(),
              '@name' => $remote_registration->getName(),
            ]);
            $this->logger->error($message);
          }
        }, function (\Exception $e) {
          if ($e instanceof ConnectException) {
            // This is expected, since we set the timeout very low.
            // @see generatePullPayload()
          }
          else {
            watchdog_exception('contentpool', $e);
          }
        }
      );

    return $promise;
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
      // Set & forget timeout, we don't wait for the response here as multiple
      // requests to satellites could take very long to update.
      RequestOptions::TIMEOUT => 0.01,
    ];
  }

}
