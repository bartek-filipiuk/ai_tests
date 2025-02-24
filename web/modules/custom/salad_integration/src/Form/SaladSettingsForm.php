<?php

namespace Drupal\salad_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SaladSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['salad_integration.settings'];
  }

  public function getFormId() {
    return 'salad_integration_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('salad_integration.settings');

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
    ];

    $form['organization'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organization'),
      '#default_value' => $config->get('organization'),
      '#required' => TRUE,
    ];

    $form['webhook_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webhook URL'),
      '#default_value' => $config->get('webhook_url'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('salad_integration.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('organization', $form_state->getValue('organization'))
      ->set('webhook_url', $form_state->getValue('webhook_url'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
