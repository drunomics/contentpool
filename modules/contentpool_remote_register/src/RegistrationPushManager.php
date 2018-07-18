<?php

namespace Drupal\contentpool_remote_register;

use Drupal\contentpool_remote_register\Entity\RemoteRegistration;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\multiversion\Workspace\ConflictTrackerInterface;
use Drupal\workspace\ReplicatorInterface;

/**
 * Helper class to get training references and backreferences.
 */
class RegistrationPushManager implements RegistrationPushManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The replicator manager.
   *
   * @var \Drupal\workspace\ReplicatorInterface
   */
  protected $replicatorManager;

  /**
   * The injected service to track conflicts during replication.
   *
   * @var \Drupal\multiversion\Workspace\ConflictTrackerInterface
   */
  protected $conflictTracker;

  /**
   * Constructs a RemoteAutopullManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StateInterface $state, ReplicatorInterface $replicator_manager, ConflictTrackerInterface $conflict_tracker) {
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
    $this->replicatorManager = $replicator_manager;
    $this->conflictTracker = $conflict_tracker;
  }

  /**
   * @inheritdoc
   */
  public function pushToRemoteRegistrations() {
    $remote_registrations = $this->entityTypeManager->getStorage('remote_registrations')->loadMultiple();

    $counter = 0;
    foreach ($remote_registrations as $remote_registration) {
      // We try to do a pull from the remote.
      $this->doPush($remote_registration);
      $counter++;
    }

    return $counter;
  }

  /**
   * {@inheritdoc}
   */
  public function doPush(RemoteRegistration $remote_registration) {
    /* @var \Drupal\workspace\WorkspacePointerInterface $parent_workspace */
    $remote_pointer = WorkspacePointer::create([
      'remote_pointer' => $remote_registration->getSiteUUID(),
      'remote_database' => $remote_registration->getDatabase()
    ]);

    // We use the live workspace or the first workspace defined.
    $workspaces = $this->entityTypeManager->getStorage('workspace')->loadMultiple();
    if (empty($workspaces)) {
      return;
    }
    $workspace = isset($workspaces['live']) ? $workspaces['live'] : reset($workspaces);

    /* @var \Drupal\workspace\WorkspacePointerInterface $source_pointer */
    $source_pointer = $this->getPointerToWorkspace($workspace);

    // Derive a replication task from the Workspace we are acting on.
    $task = $this->replicatorManager->getTask($workspace, 'push_replication_settings');
    return $this->replicatorManager->replicate($source_pointer, $remote_pointer, $task);
  }

}
