<?php

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function contentpool_channel_remote_form_taxonomy_term_channel_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $term = $form_state->getFormObject()->getEntity();

  if (!empty($term) && isset($form['field_remote_site'])) {
    // Check if term is top level; if not hide field_remote_site
    if ($term->parent->target_id) {
      $form['field_remote_site']['#access'] = FALSE;
    }
  }
}

/**
 * Implements hook_entity_update().
 *
 * Remove remote_site reference if term is no longer top level.
 */
function contentpool_channel_remote_entity_presave(EntityInterface $entity) {
  if ($entity->getEntityType()->id() == 'taxonomy_term' &&
    isset($entity->original)) {
    $parent_target_id = $entity->parent->target_id;
    $orig_parent_target_id = $entity->original->get('parent')->target_id;

    if ($entity->hasField('field_remote_site') &&
      !$entity->get('field_remote_site')->isEmpty() &&
      !$orig_parent_target_id && $parent_target_id) {
      /** @var \Drupal\contentpool_remote_register\Entity\RemoteRegistration $remote_site */
      $remote_site = $entity->field_remote_site->entity;
      $entity->field_remote_site = NULL;
      $messenger = \Drupal::messenger();
      $messenger->addWarning(t('Channel @channel has been disassociated with the remote @remote as only top-level channels can be assigned to remote sites.', [
        '@channel' => $entity->label(),
        '@remote' => $remote_site->label(),
      ]));
    }
  }
}
