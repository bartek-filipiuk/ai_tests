<?php

namespace Drupal\together_api\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\together_api\Service\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Together API settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The Together API client service.
   *
   * @var \Drupal\together_api\Service\Client
   */
  protected $client;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\together_api\Service\Client $client
   *   The Together API client service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Client $client) {
    parent::__construct($config_factory);
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('together_api.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'together_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['together_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('together_api.settings');

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t('Enter your Together API key.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Temporarily set the API key for validation
    $this->configFactory->getEditable('together_api.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->save();

    if (!$this->client->validateApiKey()) {
      $form_state->setErrorByName('api_key', $this->t('Invalid API key. Please check your credentials.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('together_api.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
