<?php

namespace Drupal\ai_page_generator\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ai_page_generator\BatchProcess;
use Drupal\openaai_assistant_api\Service\OpenAIAssistantService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for generating pages based on AI assistant input.
 */
class AiPageGeneratorForm extends FormBase {

  /**
   * The OpenAI Assistant service.
   *
   * @var \Drupal\openaai_assistant_api\Service\OpenAIAssistantService
   */
  protected $assistantService;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new AiPageGeneratorForm.
   *
   * @param \Drupal\openaai_assistant_api\Service\OpenAIAssistantService $assistant_service
   *   The OpenAI Assistant service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(OpenAIAssistantService $assistant_service, ConfigFactoryInterface $config_factory, StateInterface $state) {
    $this->assistantService = $assistant_service;
    $this->configFactory = $config_factory;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openaai_assistant_api.assistant_service'),
      $container->get('config.factory'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ai_page_generator_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Prompt for Assistant'),
      '#description' => $this->t('Enter your prompt for the AI assistant.'),
      '#rows' => 5,
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate Pages'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $prompt = $form_state->getValue('prompt');
    $config = $this->configFactory->get('openaai_assistant_api.settings');
    $assistant_id = $config->get('selected_assistant');

    if (!$assistant_id) {
      $this->messenger()->addError($this->t('No assistant selected. Please select an assistant in the OpenAI Assistant API configuration.'));
      return;
    }

    try {
      $result = $this->processPrompt($prompt, $assistant_id);
      if ($result['success']) {
        $this->startBatchProcess($result['data'], $result['raw_json']);
      } else {
        throw new \Exception($result['message']);
      }
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error generating pages: @message', ['@message' => $e->getMessage()]));
    }
  }

  /**
   * Process the prompt and get the assistant's response.
   *
   * @param string $prompt
   *   The user's prompt.
   * @param string $assistant_id
   *   The ID of the selected assistant.
   *
   * @return array
   *   An array containing the result of the processing.
   */
  protected function processPrompt(string $prompt, string $assistant_id): array {
    $thread_id = $this->state->get('ai_page_generator.thread_id');
    $active_run_id = $this->state->get('ai_page_generator.active_run_id');

    // First attempt
    $result = $this->createAndRunThread($prompt, $assistant_id, $thread_id, $active_run_id);

    // If the first attempt fails, try again with a new thread
    if (!$result['success']) {
      $result = $this->createAndRunThread($prompt, $assistant_id);
    }

    return $result;
  }

  /**
   * Create and run a thread, or use an existing one.
   *
   * @param string $prompt
   *   The user's prompt.
   * @param string $assistant_id
   *   The ID of the selected assistant.
   * @param string|null $thread_id
   *   The existing thread ID, if any.
   * @param string|null $active_run_id
   *   The existing run ID, if any.
   *
   * @return array
   *   An array containing the result of the operation.
   */
  protected function createAndRunThread(string $prompt, string $assistant_id, ?string $thread_id = null, ?string $active_run_id = null): array {
    try {
      // If no thread_id, create a new thread
      if (!$thread_id) {
        $thread = $this->assistantService->createThread();
        $thread_id = $thread['id'];
        $this->state->set('ai_page_generator.thread_id', $thread_id);
      }

      // Add the new message to the thread
      $this->assistantService->addMessageToThread($thread_id, [
        'role' => 'user',
        'content' => $prompt,
      ]);

      // Create a new run
      $run = $this->assistantService->createRun($thread_id, [
        'assistant_id' => $assistant_id,
      ]);
      $active_run_id = $run['id'];
      $this->state->set('ai_page_generator.active_run_id', $active_run_id);

      // Wait for the run to complete
      $status = $this->waitForRunCompletion($thread_id, $active_run_id);

      if ($status === 'completed') {
        $messages = $this->assistantService->listMessages($thread_id, ['limit' => 1, 'order' => 'desc']);

        if (!empty($messages['data'])) {
          $assistant_message = $messages['data'][0];
          if ($assistant_message['role'] === 'assistant') {
            $content = $assistant_message['content'][0]['text']['value'];

            // Extract JSON from the content if it's wrapped in other text
            $json_content = $this->extractJsonFromContent($content);

            if ($json_content) {
              $data = json_decode($json_content, TRUE);

              if (json_last_error() === JSON_ERROR_NONE && isset($data[0]['pages']) && is_array($data[0]['pages'])) {
                return [
                  'success' => TRUE,
                  'data' => $data,
                  'raw_json' => $json_content,
                ];
              } else {
                return [
                  'success' => FALSE,
                  'message' => "Invalid JSON structure in assistant's response.",
                ];
              }
            } else {
              return [
                'success' => FALSE,
                'message' => "No valid JSON found in assistant's response.",
              ];
            }
          } else {
            return [
              'success' => FALSE,
              'message' => "Last message in the thread is not from the assistant.",
            ];
          }
        } else {
          return [
            'success' => FALSE,
            'message' => "No messages found in the thread.",
          ];
        }
      } else {
        return [
          'success' => FALSE,
          'message' => "Run failed or timed out. Status: " . $status,
        ];
      }
    } catch (\Exception $e) {
      return [
        'success' => FALSE,
        'message' => $e->getMessage(),
      ];
    }
  }

  /**
   * Wait for a run to complete.
   *
   * @param string $thread_id
   *   The ID of the thread.
   * @param string $run_id
   *   The ID of the run.
   *
   * @return string
   *   The final status of the run.
   */
  protected function waitForRunCompletion($thread_id, $run_id) {
    $status = 'queued';
    $start_time = time();
    $timeout = 300; // 5 minutes timeout

    while (!in_array($status, ['completed', 'failed', 'cancelled']) && (time() - $start_time < $timeout)) {
      sleep(5); // Wait for 5 second before checking again
      $run = $this->assistantService->retrieveRun($thread_id, $run_id);
      $status = $run['status'];

      if ($status === 'requires_action') {
        // Handle required actions here if needed
        // For now, we'll just wait for the next check
      }
    }

    return $status;
  }

  /**
   * Starts the batch process for page generation.
   *
   * @param array $data
   *   The parsed JSON data containing page information.
   * @param string $raw_json
   *   The raw JSON string from the assistant's response.
   */
  protected function startBatchProcess(array $data, string $raw_json) {
    $operations = [];
    foreach ($data[0]['pages'] as $page) {
      $operations[] = [
        [BatchProcess::class, 'createPage'],
        [$page],
      ];
    }

    $batch = [
      'title' => $this->t('Generating pages'),
      'operations' => $operations,
      'finished' => [BatchProcess::class, 'finished'],
      'init_message' => $this->t('Starting page generation.'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('An error occurred during processing'),
    ];

    // Add the raw JSON to the batch so we can access it in the finished callback
    $batch['raw_json'] = $raw_json;

    batch_set($batch);
  }

  /**
   * Extracts JSON from the content if it's wrapped in other text.
   *
   * @param string $content
   *   The content to extract JSON from.
   *
   * @return string|null
   *   The extracted JSON string or null if no JSON is found.
   */
  protected function extractJsonFromContent(string $content): ?string {
    // First, check if the entire content is already valid JSON
    $json = json_decode($content, true);
    if (json_last_error() === JSON_ERROR_NONE) {
      return $content;
    }

    // If not, try to find JSON wrapped in code blocks
    if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $content, $matches)) {
      return $matches[1];
    }

    // If no code blocks, try to find JSON between curly braces
    if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
      return $matches[0];
    }

    // If no JSON-like structure is found, return null
    return null;
  }
}
