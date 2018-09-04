<?php

namespace Drupal\contentpool_remote_register\Form;

use Drupal\contentpool_remote_register\Entity\RemoteRegistrationInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;

/**
 * Form to enable/disable push notifications for the remote.
 */
class RemoteRegistrationPushNotificationsForm extends ConfirmFormBase {

  /**
   * The remote registration entity.
   *
   * @var \Drupal\contentpool_remote_register\Entity\RemoteRegistrationInterface
   */
  protected $remoteRegistration;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "remote_registration_push_notifications";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, RemoteRegistrationInterface $remote_registration = NULL) {
    $this->remoteRegistration = $remote_registration;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->remoteRegistration->setPushNotifications(!$this->remoteRegistration->getPushNotifications());
    $this->remoteRegistration->save();
    $form_state->setRedirect('entity.remote_registration.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.remote_registration.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $params = [
      '%name' => $this->remoteRegistration->getName(),
      '%url' => $this->remoteRegistration->getUrl(),
    ];

    if ($this->remoteRegistration->getPushNotifications()) {
      return t('Disable push notifications for %name (%url)?', $params);
    }
    else {
      return t('Enable push notifications for %name (%url)?', $params);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->remoteRegistration->getPushNotifications()) {
      return t('Disable push notifications');
    }
    else {
      return t('Enable push notifications');
    }
  }

}
