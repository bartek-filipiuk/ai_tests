<?php

namespace Drupal\openaai_assistant_api\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\ai\Service\AiProviderFormHelper;
use OpenAI\Client;

/**
 * Service for interacting with OpenAI Assistant API.
 */
class OpenAIAssistantService {

  /**
   * The AI provider plugin manager.
   *
   * @var \Drupal\ai\AiProviderPluginManager
   */
  protected $aiProviderManager;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The AI Provider Form Helper.
   *
   * @var \Drupal\ai\Service\AiProviderFormHelper
   */
  protected $aiProviderFormHelper;

  /**
   * The OpenAI client.
   *
   * @var \OpenAI\Client
   */
  protected $client;

  /**
   * Constructs a new OpenAIAssistantService object.
   *
   * @param \Drupal\ai\AiProviderPluginManager $ai_provider_manager
   *   The AI provider plugin manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\ai\Service\AiProviderFormHelper $ai_provider_form_helper
   *   The AI Provider Form Helper.
   */
  public function __construct(
    AiProviderPluginManager $ai_provider_manager,
    LoggerChannelFactoryInterface $logger_factory,
    AiProviderFormHelper $ai_provider_form_helper
  ) {
    $this->aiProviderManager = $ai_provider_manager;
    $this->logger = $logger_factory->get('openaai_assistant_api');
    $this->aiProviderFormHelper = $ai_provider_form_helper;
    $this->initializeClient();
  }

  /**
   * Initialize the OpenAI client.
   */
  protected function initializeClient() {
    try {
      $this->client = \OpenAI::client('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');
    }
    catch (\Exception $e) {
      $this->logger->error('Error initializing OpenAI client: @error', ['@error' => $e->getMessage()]);
    }
  }

  /**
   * Get the list of assistants.
   *
   * @return array
   *   An array containing the assistants data.
   */
  public function getAssistantsList(): array {
    try {
      if (!$this->client) {
        throw new \Exception('OpenAI client is not initialized.');
      }

      $response = $this->client->assistants()->list([
        'limit' => 10,
      ]);

      return $response->toArray();
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching assistants list: @error', ['@error' => $e->getMessage()]);
      return ['data' => []];
    }
  }

  /**
   * Retrieve a specific assistant.
   *
   * @param string $assistantId
   *   The ID of the assistant to retrieve.
   *
   * @return array
   *   An array containing the assistant data.
   */
  public function retrieveAssistant(string $assistantId): array {
    try {
      $response = $this->client->assistants()->retrieve($assistantId);
      return $response->toArray();
    }
    catch (\Exception $e) {
      $this->logger->error('Error retrieving assistant: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Create and run a thread with a message.
   *
   * @param string $assistantId
   *   The ID of the assistant to use.
   * @param array $message
   *   The message to send.
   *
   * @return array
   *   An array containing the response data.
   */
  public function threadsCreateAndRun(string $assistantId, array $message): array {
    try {
      $response = $this->client->threads()->createAndRun([
        'assistant_id' => $assistantId,
        'thread' => [
          'messages' => [
            [
              'role' => $message[0]['role'],
              'content' => $message[0]['content'],
            ],
          ],
        ],
      ]);

      return $response->toArray();
    }
    catch (\Exception $e) {
      $this->logger->error('Error creating and running thread: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Retrieve a thread.
   *
   * @param string $threadId
   *   The ID of the thread to retrieve.
   *
   * @return array
   *   An array containing the thread data.
   */
  public function retrieveThread(string $threadId): array {
    try {
      $response = $this->client->threads()->retrieve($threadId);
      return $response->toArray();
    }
    catch (\Exception $e) {
      $this->logger->error('Error retrieving thread: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Create a message in a thread.
   *
   * @param string $threadId
   *   The ID of the thread.
   * @param string $role
   *   The role of the message sender.
   * @param string $content
   *   The content of the message.
   *
   * @return array
   *   An array containing the created message data.
   */
  public function createMessage(string $threadId, string $role, string $content): array {
    try {
      $response = $this->client->threads()->messages()->create($threadId, [
        'role' => $role,
        'content' => $content,
      ]);

      return $response->toArray();
    }
    catch (\Exception $e) {
      $this->logger->error('Error creating message: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Retrieve a message from a thread.
   *
   * @param string $threadId
   *   The ID of the thread.
   * @param string $messageId
   *   The ID of the message to retrieve.
   *
   * @return array
   *   An array containing the message data.
   */
  public function retrieveMessage(string $threadId, string $messageId): array {
    try {
      $response = $this->client->threads()->messages()->retrieve(
        threadId: $threadId,
        messageId: $messageId,
      );

      return $response->toArray();
    }
    catch (\Exception $e) {
      $this->logger->error('Error retrieving message: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Create a run for a thread.
   *
   * @param string $assistantId
   *   The ID of the assistant to use.
   * @param string $threadId
   *   The ID of the thread.
   *
   * @return array
   *   An array containing the created run data.
   */
  public function createThreadRun(string $assistantId, string $threadId): array {
    try {
      $response = $this->client->threads()->runs()->create(
        threadId: $threadId,
        parameters: [
          'assistant_id' => $assistantId,
        ]
      );
      return $response->toArray();
    }
    catch (\Exception $e) {
      $this->logger->error('Error creating thread run: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Retrieve a run from a thread.
   *
   * @param string $threadId
   *   The ID of the thread.
   * @param string $runId
   *   The ID of the run to retrieve.
   *
   * @return array
   *   An array containing the run data.
   */
  public function retrieveThreadRun(string $threadId, string $runId): array {
    try {
      $response = $this->client->threads()->runs()->retrieve(
        threadId: $threadId,
        runId: $runId
      );

      return $response->toArray();
    }
    catch (\Exception $e) {
      $this->logger->error('Error retrieving thread run: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * List runs for a thread.
   *
   * @param string $threadId
   *   The ID of the thread.
   *
   * @return array
   *   An array containing the list of runs.
   */
  public function runsList(string $threadId): array {
    try {
      $response = $this->client->threads()->runs()->list(
        $threadId,
        ['limit' => 10]
      );
      return $response->toArray();
    }
    catch (\Exception $e) {
      $this->logger->error('Error listing runs: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Modify a message in a thread.
   *
   * @param string $threadId
   *   The ID of the thread.
   * @param string $messageId
   *   The ID of the message to modify.
   *
   * @return array
   *   An array containing the modified message data.
   */
  public function modifyMessage(string $threadId, string $messageId): array {
    try {
      $response = $this->client->threads()->messages()->modify(
        threadId: $threadId,
        messageId: $messageId,
        parameters:  [
          'metadata' => [
            'name' => 'My new message name',
          ],
        ],
      );

      return $response->toArray();
    }
    catch (\Exception $e) {
      $this->logger->error('Error modifying message: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * List messages in a thread.
   *
   * @param string $threadId
   *   The ID of the thread.
   *
   * @return array
   *   An array containing the list of messages.
   */
  public function messagesList(string $threadId): array {
    try {
      $response = $this->client->threads()->messages()->list(
        $threadId,
        ['limit' => 100,]
      );
      return $response->toArray();
    }
    catch (\Exception $e) {
      $this->logger->error('Error listing messages: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Upload a file for use with assistants.
   *
   * @param string $purpose
   *   The purpose of the file.
   * @param \Drupal\file\Entity\File $file
   *   The file to upload.
   *
   * @return array
   *   An array containing the uploaded file data.
   */
  public function uploadFile(string $purpose, \Drupal\file\Entity\File $file): array {
    $uri = $file->getFileUri();
    if ($openFile = fopen($uri, 'r')) {
      try {
        $response = $this->client->files()->upload([
          'purpose' => $purpose,
          'file' => $openFile,
        ]);
        return $response->toArray();
      }
      catch (\Exception $e) {
        $this->logger->error('Error uploading file: @error', ['@error' => $e->getMessage()]);
        return [];
      }
    }
    $this->logger->error('Assistant upload file error: Unable to open file.');
    return [];
  }

  /**
   * Create an assistant file.
   *
   * @param string $assistantId
   *   The ID of the assistant.
   * @param string $fileId
   *   The ID of the file to associate with the assistant.
   *
   * @return array
   *   An array containing the created assistant file data.
   */
  public function createAssistantFile(string $assistantId, string $fileId): array {
    try {
      $response = $this->client->assistants()->files()->create($assistantId, [
        'file_id' => $fileId,
      ]);
      return $response->toArray();
    }
    catch (\Exception $e) {
      $this->logger->error('Error creating assistant file: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Submit tool outputs for a run.
   *
   * @param string $threadId
   *   The ID of the thread.
   * @param string $runId
   *   The ID of the run.
   * @param string $callId
   *   The ID of the tool call.
   * @param string $output
   *   The output of the tool.
   *
   * @return array
   *   An array containing the response data.
   */
  public function submitToolOutputs(string $threadId, string $runId, string $callId, string $output): array {
    try {
      $response = $this->client->threads()->runs()->submitToolOutputs(
        threadId: $threadId,
        runId: $runId,
        parameters:  [
          'tool_outputs' => [
            [
              'tool_call_id' => $callId,
              'output' => $output,
            ],
          ],
        ],
      );
      return $response->toArray();
    }
    catch (\Exception $e) {
      $this->logger->error('Error submitting tool outputs: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * List threads.
   *
   * @param array $parameters
   *   Optional parameters for the request.
   *
   * @return array
   *   An array containing the list of threads.
   */
  public function listThreads(array $parameters = []): array {
    try {
      $response = $this->client->threads()->list($parameters);
      return $response->toArray();
    }
    catch (\Exception $e) {
      $this->logger->error('Error listing threads: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * List runs for a thread.
   *
   * @param string $threadId
   *   The ID of the thread.
   * @param array $parameters
   *   Optional parameters for the request.
   *
   * @return array
   *   An array containing the list of runs.
   */
  public function listRuns(string $threadId, array $parameters = []): array {

    try {
      $response = $this->client->threads()->runs()->list($threadId, $parameters);
      return $response->toArray();
    }
    catch (\Exception $e) {
      $this->logger->error('Error listing runs: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Create a new thread.
   *
   * @param array $parameters
   *   Optional parameters for creating the thread.
   *
   * @return array
   *   An array containing the created thread data.
   */
  public function createThread(array $parameters = []): array {
    try {
      $response = $this->client->threads()->create($parameters);
      return $response->toArray();
    }
    catch (\Exception $e) {
      $this->logger->error('Error creating thread: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Add a new message to a thread.
   *
   * @param string $threadId
   *   The ID of the thread.
   * @param array $parameters
   *   Parameters for creating the message.
   *
   * @return array
   *   An array containing the created message data.
   */
  public function addMessageToThread(string $threadId, array $parameters): array {
    try {
      $response = $this->client->threads()->messages()->create($threadId, $parameters);
      return $response->toArray();
    }
    catch (\Exception $e) {
      $this->logger->error('Error adding message to thread: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Create a new run for a thread.
   *
   * @param string $threadId
   *   The ID of the thread.
   * @param array $parameters
   *   Parameters for creating the run.
   *
   * @return array
   *   An array containing the created run data.
   */
  public function createRun(string $threadId, array $parameters): array {
    try {
      $response = $this->client->threads()->runs()->create($threadId, $parameters);
      return $response->toArray();
    }
    catch (\Exception $e) {
      $this->logger->error('Error creating run: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * List messages in a thread.
   *
   * @param string $threadId
   *   The ID of the thread.
   * @param array $parameters
   *   Optional parameters for the request.
   *
   * @return array
   *   An array containing the list of messages.
   */
  public function listMessages(string $threadId, array $parameters = []): array {
    try {
      $response = $this->client->threads()->messages()->list($threadId, $parameters);
      return $response->toArray();
    }
    catch (\Exception $e) {
      $this->logger->error('Error listing messages: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Retrieve a run.
   *
   * @param string $thread_id
   *   The ID of the thread.
   * @param string $run_id
   *   The ID of the run.
   *
   * @return array
   *   An array containing the run data.
   */
  public function retrieveRun(string $thread_id, string $run_id): array {
    try {
      $response = $this->client->threads()->runs()->retrieve($thread_id, $run_id);
      return $response->toArray();
    }
    catch (\Exception $e) {
      $this->logger->error('Error retrieving run: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Wait for a run to complete and get the response.
   *
   * @param string $thread_id
   *   The thread ID.
   * @param string $run_id
   *   The run ID.
   * @param int $timeout
   *   Maximum time to wait in seconds.
   * @param int $interval
   *   Time between checks in seconds.
   *
   * @return array
   *   The final response data.
   */
  public function waitForResponse(string $thread_id, string $run_id, int $timeout = 300, int $interval = 1): array {
    $start_time = time();
    
    while (time() - $start_time < $timeout) {
      $run = $this->retrieveRun($thread_id, $run_id);
      
      if ($run['status'] === 'completed') {
        $messages = $this->listMessages($thread_id, ['limit' => 1, 'order' => 'desc']);
        if (!empty($messages['data'])) {
          $last_message = $messages['data'][0];
          if ($last_message['role'] === 'assistant') {
            return [
              'status' => 'completed',
              'content' => $last_message['content'][0]['text']['value'] ?? '',
            ];
          }
        }
        break;
      }
      elseif (in_array($run['status'], ['failed', 'cancelled', 'expired'])) {
        throw new \Exception("Run failed with status: {$run['status']}");
      }
      
      sleep($interval);
    }
    
    throw new \Exception('Timeout waiting for assistant response');
  }

  /**
   * Get the status of a run.
   *
   * @param string $thread_id
   *   The thread ID.
   * @param string $run_id
   *   The run ID.
   *
   * @return array
   *   The run status data.
   */
  public function getRun(string $thread_id, string $run_id): array {
    return $this->retrieveRun($thread_id, $run_id);
  }

}
