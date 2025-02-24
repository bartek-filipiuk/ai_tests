<?php

namespace Drupal\salad_integration;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Provides a client for interacting with the Salad API.
 */
class SaladApiClient {

  protected $httpClient;
  protected $config;
  protected $logger;

  /**
   * Constructs a new SaladApiClient object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->httpClient = $http_client;
    $this->config = $config_factory->get('salad_integration.settings');
    $this->logger = $logger_factory->get('salad_integration');
  }

  /**
   * Sends a transcription request to the Salad API.
   *
   * @param array $input
   *   The input parameters for the transcription request.
   *
   * @return array
   *   The decoded JSON response from the API.
   */
  public function transcribe(array $input) {
    $api_key = $this->config->get('api_key');
    $organization = $this->config->get('organization');

    $url = "https://api.salad.com/api/public/organizations/{$organization}/inference-endpoints/transcribe/jobs";

    try {
      $response = $this->httpClient->post($url, [
        'headers' => [
          'Salad-Api-Key' => $api_key,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'input' => $input,
          'webhook' => $this->config->get('webhook_url'),
        ],
      ]);

      return json_decode($response->getBody(), TRUE);
    } catch (RequestException $e) {
      $this->logger->error('API request failed: @error', ['@error' => $e->getMessage()]);
      $this->logger->error('Request body: @body', ['@body' => json_encode([
        'input' => $input,
        'webhook' => $this->config->get('webhook_url'),
      ])]);
      if ($e->hasResponse()) {
        $this->logger->error('Response body: @body', ['@body' => $e->getResponse()->getBody()->getContents()]);
      }
      throw $e;
    }
  }

  /**
   * Retrieves the status of a transcription job from the Salad API.
   *
   * @param string $job_id
   *   The ID of the transcription job.
   *
   * @return array
   *   The decoded JSON response from the API.
   */
  public function getTranscriptionStatus($job_id) {
    $api_key = $this->config->get('api_key');
    $organization = $this->config->get('organization');

    $url = "https://api.salad.com/api/public/organizations/{$organization}/inference-endpoints/transcribe/jobs/{$job_id}";

    $response = $this->httpClient->get($url, [
      'headers' => [
        'Salad-Api-Key' => $api_key,
      ],
    ]);

    return json_decode($response->getBody(), TRUE);
  }
}
