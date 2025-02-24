<?php

namespace Drupal\telegram_assistant\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Drupal\openaai_assistant_api\Service\OpenAIAssistantService;

/**
 * Configures Telegram Assistant settings.
 */
class TelegramAssistantSettingsForm extends ConfigFormBase {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The OpenAI Assistant service.
   *
   * @var \Drupal\openaai_assistant_api\Service\OpenAIAssistantService
   */
  protected $assistantService;

  /**
   * Constructs a TelegramAssistantSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\openaai_assistant_api\Service\OpenAIAssistantService $assistant_service
   *   The OpenAI Assistant service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ClientInterface $http_client,
    OpenAIAssistantService $assistant_service
  ) {
    parent::__construct($config_factory);
    $this->httpClient = $http_client;
    $this->assistantService = $assistant_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client'),
      $container->get('openaai_assistant_api.assistant_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'telegram_assistant_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['telegram_assistant.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('telegram_assistant.settings');

    $form['telegram_bot_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Telegram Bot Token'),
      '#description' => $this->t('Enter your Telegram Bot API token.'),
      '#default_value' => $config->get('telegram_bot_token'),
      '#required' => TRUE,
    ];

    // Add assistant ID field with available assistants as options
    $assistants = $this->assistantService->getAssistantsList();
    $options = [];
    foreach ($assistants['data'] as $assistant) {
      $options[$assistant['id']] = $assistant['name'] . ' (' . $assistant['id'] . ')';
    }

    $form['assistant_id'] = [
      '#type' => 'select',
      '#title' => $this->t('OpenAI Assistant'),
      '#description' => $this->t('Select the OpenAI Assistant to use for chat.'),
      '#options' => $options,
      '#default_value' => $config->get('assistant_id'),
      '#required' => TRUE,
    ];

    $form['register_webhook'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and Register Webhook'),
      '#submit' => ['::submitForm', '::registerWebhook'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('telegram_assistant.settings')
      ->set('telegram_bot_token', $form_state->getValue('telegram_bot_token'))
      ->set('assistant_id', $form_state->getValue('assistant_id'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Custom submit handler to register the webhook.
   */
  public function registerWebhook(array &$form, FormStateInterface $form_state) {
    $token = $form_state->getValue('telegram_bot_token');
    
    // Podczas developmentu użyj URL z ngrok
    $base_url = 'https://a301-195-206-112-203.ngrok-free.app';
    $webhook_url = $base_url . '/telegram/webhook';
    
    try {
      $response = $this->httpClient->request('POST', "https://api.telegram.org/bot{$token}/setWebhook", [
        'json' => [
          'url' => $webhook_url,
        ],
      ]);

      $result = json_decode((string) $response->getBody(), TRUE);
      
      if ($result['ok']) {
        $this->messenger()->addMessage($this->t('Webhook successfully registered with URL: @url', ['@url' => $webhook_url]));
      }
      else {
        $this->messenger()->addError($this->t('Failed to register webhook: @error', ['@error' => $result['description']]));
      }
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error registering webhook: @error', ['@error' => $e->getMessage()]));
    }
  }
}
