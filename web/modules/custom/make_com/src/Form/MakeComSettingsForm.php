<?php

namespace Drupal\make_com\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class MakeComSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['make_com.settings'];
  }

  public function getFormId() {
    return 'make_com_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('make_com.settings');

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
    ];

    $form['api_url'] = [
      '#type' => 'url',
      '#title' => $this->t('API URL'),
      '#default_value' => $config->get('api_url'),
      '#required' => TRUE,
    ];

    $form['team_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Team ID'),
      '#default_value' => $config->get('team_id'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('make_com.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('api_url', $form_state->getValue('api_url'))
      ->set('team_id', $form_state->getValue('team_id'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}