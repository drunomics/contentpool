<?php

namespace Drupal\contentpool_remote_register;

use Drupal\contentpool_remote_register\Entity\RemoteRegistration;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\multiversion\Workspace\ConflictTrackerInterface;
use Drupal\workspace\Entity\WorkspacePointer;
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
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ReplicatorInterface $replicator_manager, ConflictTrackerInterface $conflict_tracker) {
    $this->entityTypeManager = $entity_type_manager;
    $this->replicatorManager = $replicator_manager;
    $this->conflictTracker = $conflict_tracker;
  }

  /**
   * @inheritdoc
   */
  public function pushToRemoteRegistrations() {
    $remote_registrations = $this->entityTypeManager->getStorage('remote_registration')->loadMultiple();

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
    $remote_pointer = GhostWorkspacePointer::create([
      'remote_database' => $remote_registration->getDatabaseId(),
    ]);

    $remote_pointer->setUri($remote_registration->getEndpointUri());
    $remote_pointer->setDatabaseId($remote_registration->getDatabaseId());

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

  /**
   * Returns a pointer to the specified workspace.
   *
   * In most cases this pointer will be unique, but that is not guaranteed
   * by the schema. If there are multiple pointers, which one is returned is
   * undefined.
   *
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   *   The workspace for which we want a pointer.
   *
   * @return \Drupal\workspace\WorkspacePointerInterface
   *   The pointer to the provided workspace.
   */
  protected function getPointerToWorkspace(WorkspaceInterface $workspace) {
    $pointers = $this->entityTypeManager
      ->getStorage('workspace_pointer')
      ->loadByProperties(['workspace_pointer' => $workspace->id()]);
    $pointer = reset($pointers);
    return $pointer;
  }

}
