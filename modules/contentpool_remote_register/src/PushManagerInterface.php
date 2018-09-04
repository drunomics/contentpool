<?php

namespace Drupal\contentpool_remote_register;

use Drupal\contentpool_remote_register\Entity\RemoteRegistrationInterface;

/**
 * Interface for RemoteAutopullManager.
 */
interface PushManagerInterface {

  /**
   * Pushes to all registered remotes.
   */
  public function pushToRegisteredRemotes();

  /**
   * Trigger remote to pull new content from the contentpool.
   *
   * @param \Drupal\contentpool_remote_register\Entity\RemoteRegistrationInterface $remote_registration
   *   The remote registration entity.
   * @param bool $asynchronous
   *   Defaults to FALSE (= synchronous request).
   *   If set to TRUE, then the returned Promise must be handled by the calling
   *   scope by invoking the wait() method.
   *   Use it when triggering multiple remotes at once or when you want to
   *   execute other code and put the Promise::wait() at the end.
   *
   * @return \GuzzleHttp\Promise\PromiseInterface
   *   The promise.
   */
  public function triggerPullAtRemote(RemoteRegistrationInterface $remote_registration, $asynchronous = FALSE);

}
