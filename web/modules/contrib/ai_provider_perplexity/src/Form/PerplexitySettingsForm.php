<?php

namespace Drupal\ai_perplexity\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ai\AiProviderPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Perplexity AI API access.
 */
class PerplexitySettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   */
  const CONFIG_NAME = 'ai_perplexity.settings';

  /**
   * The AI Provider service.
   *
   * @var \Drupal\ai\AiProviderPluginManager
   */
  protected $aiProviderManager;

  /**
   * Constructs a new PerplexitySettingsForm object.
   */
  final public function __construct(AiProviderPluginManager $ai_provider_manager) {
    $this->aiProviderManager = $ai_provider_manager;
  }

  /**
   * {@inheritdoc}
   */
  final public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ai.provider'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'perplexity_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::CONFIG_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::CONFIG_NAME);

    $form['api_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Perplexity AI API Key'),
      '#description' => $this->t('The API Key. Can be found on <a href="https://www.perplexity.ai/settings/api">https://www.perplexity.ai/settings/api</a>.'),
      '#default_value' => $config->get('api_key'),
    ];

    $form['default_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Model'),
      '#options' => [
        'llama-3.1-sonar-small-128k-online' => $this->t('Llama 3.1 Sonar Small (8B)'),
        'llama-3.1-sonar-large-128k-online' => $this->t('Llama 3.1 Sonar Large (70B)'),
        'llama-3.1-sonar-huge-128k-online' => $this->t('Llama 3.1 Sonar Huge (405B)'),
      ],
      '#default_value' => $config->get('default_model') ?? 'llama-3.1-sonar-small-128k-online',
      '#required' => TRUE,
    ];

    $form['model_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Model Settings'),
      '#open' => TRUE,
    ];

    $form['model_settings']['temperature'] = [
      '#type' => 'number',
      '#title' => $this->t('Temperature'),
      '#default_value' => $config->get('temperature') ?? 0.2,
      '#min' => 0,
      '#max' => 2,
      '#step' => 0.1,
      '#description' => $this->t('Controls randomness in the response (0-2).'),
    ];

    $form['model_settings']['top_p'] = [
      '#type' => 'number',
      '#title' => $this->t('Top P'),
      '#default_value' => $config->get('top_p') ?? 0.9,
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.1,
      '#description' => $this->t('Controls diversity via nucleus sampling (0-1).'),
    ];

    $form['model_settings']['max_tokens'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Tokens'),
      '#default_value' => $config->get('max_tokens') ?? 1000,
      '#min' => 1,
      '#max' => 4096,
      '#description' => $this->t('Maximum number of tokens to generate.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->config(static::CONFIG_NAME)
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('default_model', $form_state->getValue('default_model'))
      ->set('temperature', $form_state->getValue('temperature'))
      ->set('top_p', $form_state->getValue('top_p'))
      ->set('max_tokens', $form_state->getValue('max_tokens'))
      ->save();

    $this->aiProviderManager->defaultIfNone('chat', 'perplexity', $form_state->getValue('default_model'));

    parent::submitForm($form, $form_state);
  }

} 