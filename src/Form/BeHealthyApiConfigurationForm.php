<?php

namespace Drupal\be_healthy_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class BeHealthyApiConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'be_healthy_api_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'be_healthy_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('be_healthy_api.settings');
    $form['header_parameter'] = [
      '#title' => t('Header parameters'),
      '#type' => 'details',
      '#open' => TRUE,
    ];
    $form['header_parameter']['app_version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App version'),
      '#default_value' => $config->get('app_version'),
      '#required' => TRUE,
    ];
    $form['header_parameter']['community'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Community'),
      '#default_value' => $config->get('community'),
      '#required' => TRUE,
    ];
    $form['body_parameter'] = [
      '#title' => t('Body parameters'),
      '#type' => 'details',
      '#open' => TRUE,
    ];
    $form['body_parameter']['installments'] = [
      '#type' => 'number',
      '#title' => $this->t('Installments'),
      '#default_value' => $config->get('installments'),
      '#description' => $this->t('Maximum number of installments.'),
      '#field_suffix' => $this->t('installments'),
      '#min' => 1,
      '#max' => 60,
      '#required' => TRUE,
    ];
    $form['body_parameter']['refund_currency'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Refund currency'),
      '#default_value' => $config->get('refund_currency'),
      '#required' => TRUE,
    ];
    $form['body_parameter']['refund_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Refund value'),
      '#default_value' => $config->get('refund_value'),
      '#required' => TRUE,
    ];
    $form['body_parameter']['enable'] = [
      '#title' => t("Enable"),
      '#default_value' => $config->get('enable'),
      '#suffix' => 'Enable if you want an active user on admin console',
      '#type' => 'checkbox',
    ];
    $form['body_parameter']['timezone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Timezone'),
      '#default_value' => $config->get('timezone'),
    ];
    $form['authenticate'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuration authenticate'),
      '#group' => 'bootstrap',
    ];
    $form['authenticate']['success_message_authenticate'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Success message'),
      '#default_value' => $config->get('success_message_authenticate'),
      '#required' => TRUE,
    ];
    $form['authenticate']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => $config->get('email'),
      '#pattern' => '[a-zA-Z0-9][\w\-\.]{0,}[\w]{1}@[a-zA-Z0-9][\w\-]{1,}\.[a-z]{2,4}(\.[a-z]{2})?',
      '#required' => FALSE,
    ];
    $form['authenticate']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#attributes' => [
        'value' => [
          $config->get('password'),
        ],
      ],
      '#size' => 25,
      '#required' => FALSE,
    ];
    $form['account'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuration Account'),
      '#group' => 'bootstrap',
    ];
    $form['account']['success_message_account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Success message'),
      '#default_value' => $config->get('success_message_account'),
      '#required' => TRUE,
    ];
    $form['elegibility'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuration Elegibility'),
      '#group' => 'bootstrap',
    ];
    $form['elegibility']['success_message_elegibility'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Success message'),
      '#default_value' => $config->get('success_message_elegibility'),
      '#required' => TRUE,
    ];
    $form['elegibility']['community_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Community'),
      '#default_value' => $config->get('community_id'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('be_healthy_api.settings')
      ->set('app_version', $form_state->getValue('app_version'))
      ->set('community', $form_state->getValue('community'))
      ->set('installments', $form_state->getValue('installments'))
      ->set('refund_currency', $form_state->getValue('refund_currency'))
      ->set('refund_value', $form_state->getValue('refund_value'))
      ->set('enable', $form_state->getValue('enable'))
      ->set('timezone', $form_state->getValue('timezone'))
      ->set('success_message_authenticate', $form_state->getValue('success_message_authenticate'))
      ->set('email', $form_state->getValue('email'))
      ->set('password', $form_state->getValue('password'))
      ->set('success_message_account', $form_state->getValue('success_message_account'))
      ->set('success_message_elegibility', $form_state->getValue('success_message_elegibility'))
      ->set('community_id', $form_state->getValue('community_id'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
