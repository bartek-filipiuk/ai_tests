<?php

namespace Drupal\bad_module\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\LinkGenerator;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class AdminSettingsForm extends ConfigFormBase {
  
  public function getFormId() {
    return 'bad_module_admin_settings';
  }

  protected function getEditableConfigNames() {
    return ['bad_module.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('bad_module.settings');

    $form['data'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dane do zapisania'),
      '#default_value' => $config->get('data'),
      '#required' => TRUE,
    ];

    $form['info'] = [
      '#markup' => $this->t('Ten formularz demonstruje złe praktyki kodowania w Drupal 10.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()
      ->getEditable('bad_module.settings')
      ->set('data', $form_state->getValue('data'))
      ->save();

    $badService = \Drupal::service('bad_module.service');
    $result = $badService->saveData($form_state->getValue('data'));
    
    $allData = \Drupal\bad_module\BadModuleService::getData();
    
    \Drupal::messenger()->addStatus($this->t('Konfiguracja została zapisana. Wynik: @result', [
      '@result' => $result,
    ]));

    parent::submitForm($form, $form_state);
  }
}
