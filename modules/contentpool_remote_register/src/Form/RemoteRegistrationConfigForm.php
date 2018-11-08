<?php

namespace Drupal\contentpool_remote_register\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for remote registration.
 */
class RemoteRegistrationConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contentpool_remote_register_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'contentpool_remote_register.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('contentpool_remote_register.settings');

    $form['logging'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Logging and messaging'),
      'description' => [
        '#markup' => t('Configure logging and messaging when pushing to remotes.'),
      ],
    ];
    $form['logging']['logging-status'] = [
      '#type' => 'checkbox',
      '#title' => t('Logging'),
      '#default_value' => $config->get('logging_status'),
      '#description' => t('Enable logging when pushing to remotes.'),
    ];
    $form['logging']['messaging-status'] = [
      '#type' => 'checkbox',
      '#title' => t('Messaging'),
      '#default_value' => $config->get('messaging_status'),
      '#description' => t('Enable messaging when pushing to remotes.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('contentpool_remote_register.settings')
      ->set('logging_status', $form_state->getValue('logging-status'))
      ->set('messaging_status', $form_state->getValue('messaging-status'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
