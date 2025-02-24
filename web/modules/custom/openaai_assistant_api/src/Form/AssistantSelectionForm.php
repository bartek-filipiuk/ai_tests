<?php

namespace Drupal\openaai_assistant_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openaai_assistant_api\Service\OpenAIAssistantService;
use Drupal\ai\Service\AiProviderFormHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a OpenAI Assistant API form.
 */
class AssistantSelectionForm extends FormBase {

  /**
   * The OpenAI Assistant service.
   *
   * @var \Drupal\openaai_assistant_api\Service\OpenAIAssistantService
   */
  protected $assistantService;

  /**
   * The AI Provider Form Helper.
   *
   * @var \Drupal\ai\Service\AiProviderFormHelper
   */
  protected $aiProviderFormHelper;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openaai_assistant_api.assistant_service'),
      $container->get('ai.form_helper'),
      $container->get('config.factory')
    );
  }

  /**
   * Constructs a new AssistantSelectionForm.
   *
   * @param \Drupal\openaai_assistant_api\Service\OpenAIAssistantService $assistant_service
   *   The OpenAI Assistant service.
   * @param \Drupal\ai\Service\AiProviderFormHelper $ai_provider_form_helper
   *   The AI Provider Form Helper.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(OpenAIAssistantService $assistant_service, AiProviderFormHelper $ai_provider_form_helper, ConfigFactoryInterface $config_factory) {
    $this->assistantService = $assistant_service;
    $this->aiProviderFormHelper = $ai_provider_form_helper;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openaai_assistant_api_selection_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;

    $assistants_data = $this->assistantService->getAssistantsList();
    $options = [];
    if (isset($assistants_data['data']) && is_array($assistants_data['data'])) {
      foreach ($assistants_data['data'] as $assistant) {
        if (isset($assistant['id']) && isset($assistant['name'])) {
          $options[$assistant['id']] = $assistant['name'];
        }
      }
    }

    $config = $this->configFactory->get('openaai_assistant_api.settings');
    $selected_assistant = $config->get('selected_assistant');

    if (!empty($options)) {
      $form['assistant'] = [
        '#type' => 'select',
        '#title' => $this->t('Select an Assistant'),
        '#options' => $options,
        '#empty_option' => $this->t('- Select -'),
        '#required' => TRUE,
        '#default_value' => $selected_assistant,
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save Assistant'),
      ];
    }
    else {
      $form['no_assistants'] = [
        '#markup' => $this->t('No assistants available or there was an error fetching the assistants.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_assistant = $form_state->getValue('assistant');
    
    // Save the selected assistant to configuration.
    $this->configFactory->getEditable('openaai_assistant_api.settings')
      ->set('selected_assistant', $selected_assistant)
      ->save();

    $this->messenger()->addStatus($this->t('Assistant "@assistant" has been selected and saved.', ['@assistant' => $options[$selected_assistant]]));
  }

}
