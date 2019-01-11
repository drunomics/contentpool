<?php

namespace Drupal\multiversion_sequence_filter;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the replication changes factory.
 */
class MultiversionSequenceFilterServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('replication.changes_factory');
    $definition->setClass(ResolvedChangesFactory::class);
  }

}