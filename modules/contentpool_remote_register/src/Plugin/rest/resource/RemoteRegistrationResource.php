<?php

namespace Drupal\contentpool_remote_register\Plugin\rest\resource;

use Drupal\Component\Serialization\Json;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * @RestResource(
 *   id = "contentpool:remote_registration",
 *   label = "Remote registrations",
 *   uri_paths = {
 *     "create" = "/_remote-registration",
 *   }
 * )
 */
class RemoteRegistrationResource extends ResourceBase {

  public function post($data) {
    // Create new remote registration.
    $entity_storage = \Drupal::service('entity_type.manager')->getStorage('remote_registration');
    $remote_registrations = $entity_storage->loadByProperties([
      'site_uuid' => $data['site_uuid']
    ]);

    // Create new remote registration if none exists.
    if (empty($remote_registrations)) {
      /** @var \Drupal\Core\Entity\Entity $remote_registration */
      $remote_registration = $entity_storage->create([
        'site_uuid' => $data['site_uuid'],
        'name' => $data['site_name'],
        'url' => $data['site_domain'],
        'endpoint_uri' => $data['endpoint_uri'],
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
        'uuid' => \Drupal::config('system.site')->get('uuid'),
        'registration_uuid' => $remote_registration->uuid(),
      ],
      $status_code
    );
  }

}
