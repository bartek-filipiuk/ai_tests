<?php

declare(strict_types=1);

namespace Drupal\openai;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\file\Entity\File;
use OpenAI\Client;
use OpenAI\Exceptions\TransporterException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The OpenAI API wrapper class for interacting with the client.
 */
class OpenAIApi implements ContainerInjectionInterface {

  /**
   * The OpenAI client.
   *
   * @var \OpenAI\Client
   */
  protected $client;

  /**
   * The cache backend service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The logger channel factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The OpenAI API constructor.
   *
   * @param \OpenAI\Client $client
   *   The OpenAI HTTP client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger channel factory service.
   */
  public function __construct(Client $client, CacheBackendInterface $cache, LoggerChannelFactoryInterface $loggerChannelFactory) {
    $this->client = $client;
    $this->cache = $cache;
    $this->logger = $loggerChannelFactory->get('openai');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openai.client'),
      $container->get('cache.default'),
      $container->get('logger'),
    );
  }

  public function assistantsList() {
    try {
      return $this->client->assistants()->list([
        'limit' => 10,
      ])->toArray();
    } catch (TransporterException | \Exception $e) {
      $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function retrieveAssistant(string $assistantId) {
    try {
      return $this->client->assistants()->retrieve($assistantId)->toArray();
    } catch (TransporterException | \Exception $e) {
      $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function threadsCreateAndRun(string $assistantId, array $message): array {
    try {
      $response = $this->client->threads()->createAndRun([
        'assistant_id' => $assistantId,
        'thread' => [
          'messages' =>
            [
              [
                'role' => $message[0]['role'],
                'content' => $message[0]['content'],
              ],
            ],
        ],
      ]);

      $result = $response->toArray();
      return $result;
    } catch (TransporterException | \Exception $e) {
      $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function retrieveThread(string $threadId) {
    try {
      $response = $this->client->threads()->retrieve($threadId);
      return $response->toArray();
    } catch (TransporterException | \Exception $e) {
      $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function createMessage(string $threadId, string $role, string $content) {
    try {
      $response = $this->client->threads()->messages()->create($threadId, [
        'role' => $role,
        'content' => $content,
      ]);

      $result = $response->toArray();
      return $result;
    } catch (TransporterException | \Exception $e) {
      $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function retrieveMessage(string $threadId, string $messageId) {
    try {
      $response = $this->client->threads()->messages()->retrieve(
        threadId: $threadId,
        messageId: $messageId,
      );

      $result = $response->toArray();
      return $result;
    } catch (TransporterException | \Exception $e) {
      $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function createThreadRun(string $assistantId, string $threadId) {
    try {
      $response = $this->client->threads()->runs()->create(
        threadId: $threadId,
        parameters: [
          'assistant_id' => $assistantId,
        ]
      );
      return $response->toArray();
    }
    catch (TransporterException | \Exception $e) {
      $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function retrieveThreadRun(string $threadId, string $runId) {
    try {
      $response = $this->client->threads()->runs()->retrieve(
        threadId: $threadId,
        runId: $runId
      );

      $result = $response->toArray();
      return $result;
    } catch (TransporterException | \Exception $e) {
      $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function runsList(string $threadId) {
    try {
      $response = $this->client->threads()->runs()->list(
        $threadId,
        ['limit' => 10]
      );
      return $response->toArray();
    }
    catch (TransporterException | \Exception $e) {
      $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function modifyMessage(string $threadId, string $messageId) {
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

      $result = $response->toArray();
      return $result;
    } catch (TransporterException | \Exception $e) {
      $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function messagesList(string $threadId) {
    try {
      $response = $this->client->threads()->messages()->list(
        $threadId,
        ['limit' => 100,]
      );
      return $response->toArray();
    } catch (TransporterException | \Exception $e) {
      $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function uploadFile(string $purpose, File $file) {
    $uri = $file->getFileUri();
    if ($openFile = fopen($uri, 'r')) {
      try {
        $response = $this->client->files()->upload(
          [
            'purpose' => $purpose,
            'file' => $openFile,
          ]
        );
        return $response->toArray();
      }
      catch (TransporterException | \Exception $e) {
        $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
        return [];
      }
    }
    $this->logger->error('Assistant upload file error.');
    return [];
  }

  public function createAssistantFile(string $assistantId, string $fileId) {
    try {
      $response = $this->client->assistants()->files()->create($assistantId, [
        'file_id' => $fileId,
      ]);
      return $response->toArray();
    } catch (TransporterException | \Exception $e) {
      $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  public function submitToolOutputs(string $threadId, string $runId, string $callId, string $output) {
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
    catch (TransporterException | \Exception $e) {
      $this->logger->error('There was an issue obtaining a response from OpenAI. The error was @error.', ['@error' => $e->getMessage()]);
      return [];
    }
  }

}
