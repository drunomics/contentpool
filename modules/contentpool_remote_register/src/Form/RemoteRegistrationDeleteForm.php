<?php

namespace Drupal\contentpool_remote_register\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Delete translation form for remote registration entities.
 *
 * @internal
 */
class RemoteRegistrationDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'remote_registration_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, LanguageInterface $language = NULL) {
    return parent::buildForm($form, $form_state);
  }

}
