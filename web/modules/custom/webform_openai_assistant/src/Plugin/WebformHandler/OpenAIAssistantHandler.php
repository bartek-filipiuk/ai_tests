<?php

namespace Drupal\webform_openai_assistant\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Webform handler for OpenAI Assistant integration.
 *
 * @WebformHandler(
 *   id = "openai_assistant",
 *   label = @Translation("OpenAI Assistant"),
 *   category = @Translation("External"),
 *   description = @Translation("Sends form data to OpenAI Assistant and forwards response to external API."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 */
class OpenAIAssistantHandler extends WebformHandlerBase {

  use StringTranslationTrait;

  /**
   * The OpenAI Assistant service.
   *
   * @var \Drupal\openaai_assistant_api\Service\OpenAIAssistantService
   */
  protected $assistantService;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The AI provider form helper.
   *
   * @var \Drupal\ai\Service\AiProviderFormHelper
   */
  protected $aiProviderFormHelper;


  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The handler ID.
   *
   * @var string
   */
  protected $handler_id;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->assistantService = $container->get('openaai_assistant_api.assistant_service');
    $instance->aiProviderFormHelper = $container->get('ai.form_helper');
    $instance->request = $container->get('request_stack')->getCurrentRequest();
    $instance->httpClient = $container->get('http_client');
    //$instance->loggerFactory = $container->get('logger.factory');
    $instance->messenger = $container->get('messenger');
    return $instance;
  }


  /**
   * Sets the current request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return $this
   */
  protected function setRequest(Request $request) {
    $this->request = $request;
    return $this;
  }

  /**
   * Sets the HTTP client.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   *
   * @return $this
   */
  public function setHttpClient(ClientInterface $http_client) {
    $this->httpClient = $http_client;
    return $this;
  }

  /**
   * Sets the messenger.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   *
   * @return $this
   */
  public function setMessenger(MessengerInterface $messenger) {
    $this->messenger = $messenger;
    return $this;
  }

  /**
   * Sets the handler ID.
   *
   * @param string $handler_id
   *   The handler ID.
   *
   * @return $this
   */
  public function setHandlerId($handler_id) {
    $this->handler_id = $handler_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHandlerId() {
    return !empty($this->handler_id) ? $this->handler_id : parent::getHandlerId();
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    $handler_id = $this->getHandlerId();
    if (!empty($handler_id)) {
      return $handler_id;
    }
    return $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'assistant_id' => '',
      'prompt_template' => '',
      'endpoint_url' => '',
      'completed_url' => '',
      'completed_custom_data' => '',
      'updated_url' => '',
      'updated_custom_data' => '',
      'deleted_url' => '',
      'deleted_custom_data' => '',
      'draft_created_url' => '',
      'draft_created_custom_data' => '',
      'converted_url' => '',
      'converted_custom_data' => '',
      'debug' => FALSE,
      'excluded_data' => [],
      'custom_data' => '',
      'custom_options' => '',
      'method' => 'POST',
      'type' => 'x-www-form-urlencoded',
      'file_data' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Get list of available assistants
    try {
      $assistants = $this->assistantService->getAssistantsList();
      if (empty($assistants['data'])) {
        $this->messenger->addWarning($this->t('No OpenAI Assistants found. Please create at least one assistant.'));
        return $form;
      }

      $assistant_options = [];
      foreach ($assistants['data'] as $assistant) {
        $assistant_options[$assistant['id']] = $assistant['name'];
      }
    }
    catch (\Exception $e) {
      //$this->loggerFactory->get('webform_openai_assistant')->error('Error fetching assistants: @error', ['@error' => $e->getMessage()]);
      $this->messenger->addError($this->t('Unable to fetch OpenAI Assistants. Please check the logs for details.'));
      return $form;
    }

    $form['assistant_id'] = [
      '#type' => 'select',
      '#title' => $this->t('OpenAI Assistant'),
      '#options' => $assistant_options,
      '#default_value' => $this->configuration['assistant_id'],
      '#required' => TRUE,
      '#description' => $this->t('Select the OpenAI Assistant to use.'),
    ];

    $form['prompt_template'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Prompt Template'),
      '#default_value' => $this->configuration['prompt_template'],
      '#description' => $this->t('Enter the prompt template to send to the assistant. Use tokens like [webform_submission:values:field_name] to include form values. Leave empty to send the complete webform submission data.'),
    ];

    $form['endpoint_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Endpoint URL'),
      '#default_value' => $this->configuration['endpoint_url'],
      '#description' => $this->t('Enter the URL where the assistant response should be sent via POST.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['assistant_id'] = $form_state->getValue('assistant_id');
    $this->configuration['prompt_template'] = $form_state->getValue('prompt_template');
    $this->configuration['endpoint_url'] = $form_state->getValue('endpoint_url');
  }

  /**
   * Converts webform submission data to a structured string.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission.
   *
   * @return string
   *   A formatted string containing all submission data.
   */
  protected function formatSubmissionData(WebformSubmissionInterface $webform_submission) {
    $data = $webform_submission->getData();
    $elements = $webform_submission->getWebform()->getElementsDecodedAndFlattened();
    $output = [];

    foreach ($data as $key => $value) {
      $label = $elements[$key]['#title'] ?? $key;

      // Handle array values (like from checkboxes or multi-select)
      if (is_array($value)) {
        $value = implode(', ', $value);
      }

      $output[] = "{$label}: {$value}";
    }

    return implode("\n", $output);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    // Skip if this is an update
    if ($update) {
      return;
    }

    // Check if we have all required configuration
    if (empty($this->configuration['assistant_id'])) {
      return;
    }

    try {
      // Get the prompt template or use submission data if no template
      $prompt = '';
      if (!empty($this->configuration['prompt_template'])) {
        $prompt = $this->replaceTokens($this->configuration['prompt_template'], $webform_submission);
      }

      // Add formatted submission data
      $submissionData = $this->formatSubmissionData($webform_submission);
      $prompt = trim($prompt . "\n\n" . $submissionData);

      if (empty($prompt)) {
        throw new \Exception('Empty prompt after processing');
      }

      // Create a thread
      $thread = $this->assistantService->createThread();
      if (empty($thread['id'])) {
        throw new \Exception('Failed to create thread');
      }

      // Save thread ID to submission data
      $data = $webform_submission->getData();
      $data['openai_thread_id'] = $thread['id'];
      $webform_submission->setData($data);
      $webform_submission->save();

      // Add the message to the thread
      $message = $this->assistantService->createMessage($thread['id'], 'user', $prompt);
      if (empty($message['id'])) {
        throw new \Exception('Failed to create message');
      }

      // Create a run
      $run = $this->assistantService->createRun($thread['id'], [
        'assistant_id' => $this->configuration['assistant_id'],
      ]);
      if (empty($run['id'])) {
        throw new \Exception('Failed to create run');
      }

      // Wait for the response
      $response = $this->assistantService->waitForResponse($thread['id'], $run['id']);
      if (empty($response['content'])) {
        throw new \Exception('Empty response from assistant');
      }

      // If endpoint URL is configured, send the response
      if (!empty($this->configuration['endpoint_url'])) {
        $this->sendToEndpoint($response['content']);
      }
    }
    catch (\Exception $e) {
      $this->handleError($e, $webform_submission);
    }
  }

  /**
   * Sends the assistant response to the configured endpoint.
   *
   * @param array $response
   *   The response from the OpenAI Assistant.
   */
  protected function sendToEndpoint($response) {
    try {
      $options = [
        'json' => [
          'assistant_response' => $response,
        ],
        'timeout' => 30,
        'connect_timeout' => 10,
      ];

      $result = $this->httpClient->post($this->configuration['endpoint_url'], $options);

      if ($result->getStatusCode() !== 200) {
        throw new \Exception('Endpoint returned non-200 status code: ' . $result->getStatusCode());
      }
    }
    catch (\Exception $e) {
      //$this->loggerFactory->get('webform_openai_assistant')->error('Error sending response to endpoint: @error', ['@error' => $e->getMessage()]);
      $this->messenger->addError($this->t('Error forwarding the response. Please check the logs for details.'));
    }
  }

  /**
   * Replaces tokens in a string with webform submission values.
   *
   * @param string $text
   *   The text containing tokens.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity.
   * @param array $data
   *   The data.
   * @param array $options
   *   The options.
   *
   * @return string
   *   The text with tokens replaced.
   */
  protected function replaceTokens($text, ?EntityInterface $entity = NULL, array $data = [], array $options = []) {
    if ($entity instanceof WebformSubmissionInterface) {
      return parent::replaceTokens($text, $entity, $data, $options);
    }
    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $operation = $this->getWebformSubmissionOperation($form_state);
    if (!$operation) {
      return;
    }

    $configuration_name = $operation . '_url';
    $url = $this->configuration[$configuration_name];
    if (empty($url)) {
      return;
    }

    $this->request($webform_submission, $operation);
  }

  /**
   * Get a webform submission's operation from the form's state data.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return string|null
   *   A webform submission operation or NULL if the operation can't be determined.
   */
  protected function getWebformSubmissionOperation(FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();
    if (empty($trigger_element['#webform_submission_operation'])) {
      return NULL;
    }
    return $trigger_element['#webform_submission_operation'];
  }

  /**
   * Request handler for the webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission.
   * @param string $operation
   *   The operation being performed.
   */
  protected function request(WebformSubmissionInterface $webform_submission, $operation = '') {
    // Check if we have all required configuration
    if (empty($this->configuration['assistant_id'])) {
      return;
    }

    try {
      // Get the existing thread ID from submission data
      $data = $webform_submission->getData();
      $thread_id = $data['openai_thread_id'] ?? '';
      
      if (empty($thread_id)) {
        throw new \Exception('No OpenAI thread ID found for this submission');
      }

      // Get the prompt template or use submission data if no template
      $prompt = '';
      if (!empty($this->configuration['prompt_template'])) {
        $prompt = $this->replaceTokens($this->configuration['prompt_template'], $webform_submission);
      }

      // Add formatted submission data
      $submissionData = $this->formatSubmissionData($webform_submission);
      $prompt = trim($prompt . "\n\nOperation: " . $operation . "\n\n" . $submissionData);

      if (empty($prompt)) {
        throw new \Exception('Empty prompt after processing');
      }

      // Add the message to the existing thread
      $message = $this->assistantService->createMessage($thread_id, 'user', $prompt);
      if (empty($message['id'])) {
        throw new \Exception('Failed to create message');
      }

      // Create a run with the configured assistant on the existing thread
      $run = $this->assistantService->createRun($thread_id, [
        'assistant_id' => $this->configuration['assistant_id'],
      ]);
      if (empty($run['id'])) {
        throw new \Exception('Failed to create run');
      }

      // Wait for the response
      $response = $this->assistantService->waitForResponse($thread_id, $run['id']);
      if (empty($response['content'])) {
        throw new \Exception('Empty response from assistant');
      }

      // If endpoint URL is configured for this operation, send the response
      $url_key = $operation . '_url';
      if (!empty($this->configuration[$url_key])) {
        $this->sendToEndpoint($response['content'], $this->configuration[$url_key]);
      }
    }
    catch (\Exception $e) {
      $this->handleError($e, $webform_submission);
    }
  }

  /**
   * Handle the response from the remote server.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response object.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission.
   * @param string $operation
   *   The operation being performed.
   */
  protected function handleResponse(ResponseInterface $response, WebformSubmissionInterface $webform_submission, $operation) {
    $status_code = $response->getStatusCode();
    if ($status_code >= 200 && $status_code < 300) {
      $this->messenger->addStatus($this->t('Submission successfully sent to @url', [
        '@url' => $this->configuration[$operation . '_url'],
      ]));
    }
    else {
      $this->messenger->addError($this->t('Submission failed with status code @code', [
        '@code' => $status_code,
      ]));
    }
  }

  /**
   * Handle any errors that occurred during the request.
   *
   * @param \Exception $exception
   *   The exception that was thrown.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission.
   */
  protected function handleError(\Exception $exception, WebformSubmissionInterface $webform_submission) {
    $message = $exception->getMessage();
    $context = [
      '@form' => $webform_submission->getWebform()->label(),
      '@error' => $message,
      '@submission_id' => $webform_submission->id(),
    ];

    // Log error with submission context
//    $this->loggerFactory->get('webform_openai_assistant')
//      ->error('OpenAI Assistant request failed for form @form (submission: @submission_id): @error', $context);

    // Show user-friendly error message
    $this->messenger->addError($this->t('OpenAI Assistant request failed. Please try again later or contact the site administrator if the problem persists.'));

    // Add error to webform submission
    $data = $webform_submission->getData();
    $data['openai_error'] = $message;
    $webform_submission->setData($data);
    $webform_submission->save();
  }
}
