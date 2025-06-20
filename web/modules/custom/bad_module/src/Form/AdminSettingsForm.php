<?php

declare(strict_types=1);

namespace Drupal\bad_module\Form;

use Drupal\bad_module\BadModuleService;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Bad Module with proper dependency injection.
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * The bad module service.
   */
  protected BadModuleService $badModuleService;

  /**
   * Constructs an AdminSettingsForm object.
   *
   * @param \Drupal\bad_module\BadModuleService $bad_module_service
   *   The bad module service.
   */
  public function __construct(BadModuleService $bad_module_service) {
    $this->badModuleService = $bad_module_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('bad_module.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bad_module_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['bad_module.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('bad_module.settings');

    $form['data'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Data to save'),
      '#default_value' => $config->get('data') ?? '',
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['info'] = [
      '#markup' => $this->t('This form demonstrates proper Drupal 10 coding practices.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $data = $form_state->getValue('data');
    if (empty(trim($data))) {
      $form_state->setErrorByName('data', $this->t('Data field cannot be empty.'));
    }
    
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $data = trim($form_state->getValue('data'));
    
    $this->config('bad_module.settings')
      ->set('data', $data)
      ->save();

    $result = $this->badModuleService->saveData($data);
    $allData = $this->badModuleService->getData();

    $this->messenger()->addStatus($this->t('Configuration has been saved. Result: @result. Total items: @count', [
      '@result' => $result,
      '@count' => count($allData),
    ]));

    parent::submitForm($form, $form_state);
  }

}
