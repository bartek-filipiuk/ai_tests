<?php

namespace Drupal\ai_perplexity\Plugin\AiProvider;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ai\Attribute\AiProvider;
use Drupal\ai\Base\AiProviderClientBase;
use Drupal\ai\Enum\AiModelCapability;
use Drupal\ai\Exception\AiRateLimitException;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatInterface;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\OperationType\Chat\ChatOutput;
use Drupal\ai\Traits\OperationType\ChatTrait;
use Drupal\key\KeyRepositoryInterface;
use OpenAI\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;
use GuzzleHttp\Client as GuzzleClient;

/**
 * Plugin implementation of the 'perplexity' provider.
 */
#[AiProvider(
  id: 'perplexity',
  label: new TranslatableMarkup('Perplexity AI'),
)]
class PerplexityProvider extends AiProviderClientBase implements
  ContainerFactoryPluginInterface,
  ChatInterface {

  use ChatTrait;

  /**
   * The OpenAI Client for API calls.
   *
   * @var \OpenAI\Client|null
   */
  protected $client;

  /**
   * API Key.
   *
   * @var string
   */
  protected string $apiKey = '';

  /**
   * The key repository.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected KeyRepositoryInterface $keyRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->keyRepository = $container->get('key.repository');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguredModels(?string $operation_type = NULL, array $capabilities = []): array {
    return [
      'llama-3.1-sonar-small-128k-online' => 'Llama 3.1 Sonar Small (8B)',
      'llama-3.1-sonar-large-128k-online' => 'Llama 3.1 Sonar Large (70B)',
      'llama-3.1-sonar-huge-128k-online' => 'Llama 3.1 Sonar Huge (405B)',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isUsable(?string $operation_type = NULL, array $capabilities = []): bool {
    // No vision support.
    if (in_array(AiModelCapability::ChatWithImageVision, $capabilities)) {
      return FALSE;
    }
    // If its not configured, it is not usable.
    if (!$this->getConfig()->get('api_key')) {
      return FALSE;
    }
    // If its one of the bundles that Perplexity supports its usable.
    if ($operation_type) {
      return in_array($operation_type, $this->getSupportedOperationTypes());
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedOperationTypes(): array {
    return [
      'chat',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(): ImmutableConfig {
    return $this->configFactory->get('ai_perplexity.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getApiDefinition(): array {
    // Load the configuration.
    $definition = Yaml::parseFile($this->moduleHandler->getModule('ai_perplexity')->getPath() . '/definitions/api_defaults.yml');
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getModelSettings(string $model_id, array $generalConfig = []): array {
    return $generalConfig;
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthentication(mixed $authentication): void {
    // Set the new API key and reset the client.
    $this->apiKey = $authentication;
    $this->client = NULL;
  }

  /**
   * Gets the raw client.
   *
   * This is the client for inference.
   *
   * @return \OpenAI\Client
   *   The OpenAI client.
   */
  public function getClient(): Client {
    $this->loadClient();
    return $this->client;
  }

  /**
   * Loads the Perplexity Client with authentication if not initialized.
   */
  protected function loadClient(): void {
    if (!$this->client) {
      if (!$this->apiKey) {
        $this->setAuthentication($this->loadApiKey());
      }
      
      // Get timeout settings from config or use defaults
      $config = $this->getConfig();
      $timeout = $config->get('request_timeout') ?? 120;
      
      // Create Guzzle client with timeout settings
      $guzzleClient = new GuzzleClient([
        'timeout' => $timeout,
        'connect_timeout' => 10,
      ]);
      
      $this->client = \OpenAI::factory()
        ->withApiKey($this->apiKey)
        ->withBaseUri('https://api.perplexity.ai')
        ->withHttpClient($guzzleClient)
        ->make();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function loadApiKey(): string {
    $key_id = $this->getConfig()->get('api_key');
    if ($key_id && $key = $this->keyRepository->getKey($key_id)) {
      return $key->getKeyValue();
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function chat(array|string|ChatInput $input, string $model_id, array $tags = []): ChatOutput {
    $this->loadClient();
    // Normalize the input if needed.
    $chat_input = $input;
    if ($input instanceof ChatInput) {
      $chat_input = [];
      if ($this->chatSystemRole) {
        $chat_input[] = [
          'role' => 'system',
          'content' => $this->chatSystemRole,
        ];
      }
      foreach ($input->getMessages() as $message) {
        $chat_input[] = [
          'role' => $message->getRole(),
          'content' => $message->getText(),
        ];
      }
    }

    $payload = [
      'model' => $model_id,
      'messages' => $chat_input,
      'temperature' => $this->configuration['temperature'] ?? 0.2,
      'top_p' => $this->configuration['top_p'] ?? 0.9,
      'max_tokens' => $this->configuration['max_tokens'] ?? 1000,
    ];

    $config = $this->getConfig();
    $maxRetries = $config->get('max_retries') ?? 5;
    $retryDelay = $config->get('retry_delay') ?? 2000;
    $attempt = 0;

    while ($attempt <= $maxRetries) {
      try {
        if ($attempt > 0) {
          $currentDelay = $retryDelay * pow(2, $attempt - 1);
          usleep($currentDelay * 1000);
        }

        $response = $this->client->chat()->create($payload);

        if (!isset($response['choices'][0]['message'])) {
          throw new \Exception('Invalid response from Perplexity API');
        }

        // Create the message with the main response content.
        $message = new ChatMessage(
          $response['choices'][0]['message']['role'],
          $response['choices'][0]['message']['content']
        );

        // Extract citations if available.
        $metadata = [];
        if (!empty($response['citations'])) {
          $metadata['citations'] = $response['citations'];
        }

        return new ChatOutput($message, $response, $metadata);
      }
      catch (\Exception $e) {
        // Try to figure out rate limit issues.
        if (strpos($e->getMessage(), 'rate limit') !== FALSE) {
          throw new AiRateLimitException($e->getMessage());
        }
        
        // If it's the last attempt, throw the error
        if ($attempt === $maxRetries) {
          if (strpos($e->getMessage(), 'timed out') !== FALSE || 
              strpos($e->getMessage(), 'timeout') !== FALSE) {
            throw new \Exception('Request to Perplexity API timed out after ' . ($maxRetries + 1) . ' attempts. Please try again later or contact support if the issue persists.');
          }
          throw $e;
        }
        
        $attempt++;
      }
    }
  }

}
