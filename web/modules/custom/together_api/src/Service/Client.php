<?php

namespace Drupal\together_api\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service for interacting with Together API.
 */
class Client {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructor for TogetherApiClient.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Get API key from configuration.
   *
   * @return string
   *   The API key.
   */
  protected function getApiKey() {
    return $this->configFactory->get('together_api.settings')->get('api_key');
  }

  /**
   * Lists all available models.
   *
   * @return array
   *   Array of available models.
   *
   * @throws GuzzleException
   */
  public function listModels() {
    try {
      $response = $this->httpClient->request('GET', 'https://api.together.xyz/v1/models', [
        'headers' => [
          'accept' => 'application/json',
          'authorization' => 'Bearer ' . $this->getApiKey(),
        ],
      ]);

      return json_decode($response->getBody(), TRUE);
    }
    catch (GuzzleException $e) {
      $this->loggerFactory->get('together_api')->error(
        'Error fetching models list: @error',
        ['@error' => $e->getMessage()]
      );
      throw $e;
    }
  }

  /**
   * Creates a chat completion.
   *
   * @param array $messages
   *   The messages to process.
   * @param string $model
   *   The model to use.
   * @param array $options
   *   Additional options for the API call.
   *
   * @return array
   *   The API response.
   *
   * @throws GuzzleException
   */
  public function createChatCompletion(array $messages, string $model, array $options = []) {
    try {
      // Initialize request body with required fields
      $request_body = [
        'messages' => array_map(function ($message) {
          return [
            'role' => (string) $message['role'],
            'content' => (string) $message['content'],
          ];
        }, $messages),
        'model' => (string) $model,
      ];

      // Add optional parameters with their default values if not provided
      $optional_params = [
        'max_tokens' => 0,
        'stop' => [],
        'temperature' => 0.0,
        'top_p' => 0.0,
        'top_k' => 0,
        'repetition_penalty' => 0.0,
        'stream' => false,
        'logprobs' => 0,
        'echo' => false,
        'n' => 0,
        'min_p' => 0.0,
        'presence_penalty' => 0.0,
        'frequency_penalty' => 0.0,
        'seed' => null,
        'function_call' => 'none',
      ];

      // Merge provided options with defaults
      foreach ($optional_params as $param => $default) {
        $request_body[$param] = isset($options[$param]) ? $options[$param] : $default;
      }

      // Handle special parameters
      if (isset($options['logit_bias'])) {
        $request_body['logit_bias'] = (object) $options['logit_bias'];
      }

      if (isset($options['response_format'])) {
        $request_body['response_format'] = [
          'type' => $options['response_format']['type'] ?? 'text',
          'schema' => (object) ($options['response_format']['schema'] ?? []),
        ];
      }

      if (isset($options['tools'])) {
        $request_body['tools'] = array_map(function ($tool) {
          return [
            'type' => (string) $tool['type'],
            'function' => [
              'description' => (string) $tool['function']['description'],
              'name' => (string) $tool['function']['name'],
            ],
          ];
        }, $options['tools']);
      }

      if (isset($options['tool_choice'])) {
        $request_body['tool_choice'] = (string) $options['tool_choice'];
      }

      if (isset($options['safety_model'])) {
        $request_body['safety_model'] = (string) $options['safety_model'];
      }

      // Convert request body to JSON string
      $json_body = json_encode($request_body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      
      $this->loggerFactory->get('together_api')->debug(
        'Chat completion request JSON: @request',
        ['@request' => $json_body]
      );

      $response = $this->httpClient->request('POST', 'https://api.together.xyz/v1/chat/completions', [
        'headers' => [
          'accept' => 'application/json',
          'content-type' => 'application/json',
          'authorization' => 'Bearer ' . $this->getApiKey(),
        ],
        'body' => $json_body,
      ]);

      $result = json_decode($response->getBody(), TRUE);
      $this->loggerFactory->get('together_api')->debug(
        'Chat completion response: @response',
        ['@response' => json_encode($result)]
      );

      return $result;
    }
    catch (GuzzleException $e) {
      $this->loggerFactory->get('together_api')->error(
        'Error creating chat completion: @error',
        ['@error' => $e->getMessage()]
      );
      throw $e;
    }
  }

  /**
   * Creates a completion.
   *
   * @param string $prompt
   *   The prompt to process.
   * @param string $model
   *   The model to use.
   * @param array $options
   *   Additional options for the API call.
   *
   * @return array
   *   The API response.
   *
   * @throws GuzzleException
   */
  public function createCompletion(string $prompt, string $model, array $options = []) {
    try {
      // Initialize request body with required fields
      $request_body = [
        'prompt' => (string) $prompt,
        'model' => (string) $model,
      ];

      // Add optional parameters with their default values if not provided
      $optional_params = [
        'max_tokens' => 0,
        'stop' => ['string'],
        'temperature' => 0.0,
        'top_p' => 0.0,
        'top_k' => 0,
        'repetition_penalty' => 0.0,
        'stream' => true,
        'logprobs' => 0,
        'echo' => true,
        'n' => 0,
        'min_p' => 0.0,
        'presence_penalty' => 0.0,
        'frequency_penalty' => 0.0,
        'seed' => 42,
      ];

      // Merge provided options with defaults
      foreach ($optional_params as $param => $default) {
        $request_body[$param] = isset($options[$param]) ? $options[$param] : $default;
      }

      // Handle special parameters
      if (isset($options['logit_bias'])) {
        $request_body['logit_bias'] = (object) $options['logit_bias'];
      }

      if (isset($options['safety_model'])) {
        $request_body['safety_model'] = (string) $options['safety_model'];
      } else {
        $request_body['safety_model'] = 'Meta-Llama/Llama-Guard-7b';
      }

      // Convert request body to JSON string
      $json_body = json_encode($request_body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      
      $this->loggerFactory->get('together_api')->debug(
        'Completion request JSON: @request',
        ['@request' => $json_body]
      );

      $response = $this->httpClient->request('POST', 'https://api.together.xyz/v1/completions', [
        'headers' => [
          'accept' => 'application/json',
          'content-type' => 'application/json',
          'authorization' => 'Bearer ' . $this->getApiKey(),
        ],
        'body' => $json_body,
      ]);

      $result = json_decode($response->getBody(), TRUE);
      $this->loggerFactory->get('together_api')->debug(
        'Completion response: @response',
        ['@response' => json_encode($result)]
      );

      return $result;
    }
    catch (GuzzleException $e) {
      $this->loggerFactory->get('together_api')->error(
        'Error creating completion: @error',
        ['@error' => $e->getMessage()]
      );
      throw $e;
    }
  }

  /**
   * Creates an image using the Together API.
   *
   * @param string $prompt
   *   The prompt to generate the image from.
   * @param array $options
   *   Additional options for the request.
   *
   * @return array
   *   The API response data.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function createImage(string $prompt, array $options = []) {
    $request_body = [
      'prompt' => $prompt,
      'model' => $options['model'] ?? 'black-forest-labs/FLUX.1-schnell-Free',
      'steps' => $options['steps'] ?? 20,
      'seed' => $options['seed'] ?? 0,
      'n' => $options['n'] ?? 1,
      'height' => $options['height'] ?? 1024,
      'width' => $options['width'] ?? 1024,
      'negative_prompt' => $options['negative_prompt'] ?? '',
    ];

    $response = $this->httpClient->request('POST', 'https://api.together.xyz/v1/images/generations', [
      'headers' => [
        'accept' => 'application/json',
        'content-type' => 'application/json',
        'authorization' => 'Bearer ' . $this->getApiKey(),
      ],
      'body' => json_encode($request_body),
    ]);

    return json_decode($response->getBody(), TRUE);
  }

}
