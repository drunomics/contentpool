<?php

namespace Drupal\contentpool_remote_register\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RestResource(
 *   id = "contentpool:remote_registration",
 *   label = "Remote registrations",
 *   uri_paths = {
 *     "canonical" = "/_remote-registration",
 *   }
 * )
 */
class RemoteRegistrationResource extends ResourceBase {

  /**
   * @param string | \Drupal\Core\Config\Entity\ConfigEntityInterface $workspace
   *
   * @return \Drupal\rest\ResourceResponse
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function post($workspace) {
    // @todo: Implementation
    $response = new Response('DEBUG', 200);

    return $response;
  }

}
