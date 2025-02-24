<?php

namespace Drupal\make_com\Api;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Drupal\Component\Serialization\Json;

class MakeComClient {

  protected $httpClient;
  protected $config;

  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory) {
    $this->httpClient = $http_client;
    $this->config = $config_factory->get('make_com.settings');
  }

  protected function getApiKey() {
    return $this->config->get('api_key');
  }

  protected function getApiUrl() {
    return $this->config->get('api_url');
  }

  protected function request($method, $endpoint, array $options = []) {
    $url = $this->getApiUrl() . '/api/v2/' . $endpoint;
    
    $options['headers']['Authorization'] = 'Token ' . $this->getApiKey();
    $options['headers']['Content-Type'] = 'application/json';

    try {
      $response = $this->httpClient->request($method, $url, $options);
      return Json::decode($response->getBody());
    }
    catch (GuzzleException $e) {
      // Log the error or throw a custom exception
      \Drupal::logger('make_com')->error('API request failed: @message', ['@message' => $e->getMessage()]);
      throw new \Exception('Failed to communicate with Make.com API: ' . $e->getMessage());
    }
  }

  public function getScenarios($teamId, $limit = 10, $offset = 0) {
    $query = [
      'teamId' => $teamId,
      'pg[limit]' => $limit,
      'pg[offset]' => $offset,
    ];
    return $this->request('GET', 'scenarios', ['query' => $query]);
  }

  public function getScenarioDetails($scenarioId) {
    return $this->request('GET', "scenarios/{$scenarioId}");
  }

  public function getScenarioBlueprint($scenarioId) {
    return $this->request('GET', "scenarios/{$scenarioId}/blueprint");
  }

  public function getScenarioExecutions($scenarioId, $limit = 10, $offset = 0) {
    $query = [
      'pg[limit]' => $limit,
      'pg[offset]' => $offset,
    ];
    return $this->request('GET', "scenarios/{$scenarioId}/executions", ['query' => $query]);
  }

  public function activateScenario($scenarioId) {
    return $this->request('POST', "scenarios/{$scenarioId}/start");
  }

  public function deactivateScenario($scenarioId) {
    return $this->request('POST', "scenarios/{$scenarioId}/stop");
  }

  public function runScenario($scenarioId, array $data = []) {
    return $this->request('POST', "scenarios/{$scenarioId}/run", ['json' => $data]);
  }

  public function cloneScenario($scenarioId, $name, $teamId) {
    $data = [
      'name' => $name,
      'teamId' => $teamId,
    ];
    return $this->request('POST', "scenarios/{$scenarioId}/clone", ['json' => $data]);
  }

  public function updateScenario($scenarioId, array $data) {
    return $this->request('PATCH', "scenarios/{$scenarioId}", ['json' => $data]);
  }

  public function deleteScenario($scenarioId) {
    return $this->request('DELETE', "scenarios/{$scenarioId}");
  }

  // Dodaj więcej metod dla innych endpointów
}