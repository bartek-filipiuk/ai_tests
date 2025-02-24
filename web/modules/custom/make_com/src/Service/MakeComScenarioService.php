<?php

namespace Drupal\make_com\Service;

use Drupal\make_com\Api\MakeComClient;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;

class MakeComScenarioService {

  protected $client;
  protected $config;
  protected $cache;

  public function __construct(MakeComClient $client, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache) {
    $this->client = $client;
    $this->config = $config_factory->get('make_com.settings');
    $this->cache = $cache;
  }

  protected function getTeamId() {
    return $this->config->get('team_id');
  }

  public function getScenarios($limit = 10, $offset = 0) {
    $cid = 'make_com:scenarios:' . $limit . ':' . $offset;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }
    $result = $this->client->getScenarios($this->getTeamId(), $limit, $offset);
    $this->cache->set($cid, $result, time() + 3600); // Cache for 1 hour
    return $result;
  }

  public function getAllScenarios() {
    $allScenarios = [];
    $offset = 0;
    $limit = 100;

    do {
      $result = $this->getScenarios($limit, $offset);
      $allScenarios = array_merge($allScenarios, $result['scenarios']);
      $offset += $limit;
    } while (count($result['scenarios']) == $limit);

    return $allScenarios;
  }

  public function getScenarioDetails($scenarioId) {
    $cid = 'make_com:scenario:' . $scenarioId;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }
    $result = $this->client->getScenarioDetails($scenarioId);
    $this->cache->set($cid, $result, time() + 3600); // Cache for 1 hour
    return $result;
  }

  // Dodaj więcej metod według potrzeb

  public function activateScenario($scenarioId) {
    return $this->client->activateScenario($scenarioId);
  }

  public function deactivateScenario($scenarioId) {
    return $this->client->deactivateScenario($scenarioId);
  }

  public function runScenario($scenarioId, array $data = []) {
    return $this->client->runScenario($scenarioId, $data);
  }

  public function cloneScenario($scenarioId, $name, $teamId) {
    return $this->client->cloneScenario($scenarioId, $name, $teamId);
  }

  public function updateScenario($scenarioId, array $data) {
    return $this->client->updateScenario($scenarioId, $data);
  }

  public function deleteScenario($scenarioId) {
    return $this->client->deleteScenario($scenarioId);
  }

  public function getScenarioExecutions($scenarioId, $limit = 10, $offset = 0) {
    $cid = 'make_com:executions:' . $scenarioId . ':' . $limit . ':' . $offset;
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }
    $result = $this->client->getScenarioExecutions($scenarioId, $limit, $offset);
    $this->cache->set($cid, $result, time() + 1800); // Cache for 30 minutes
    return $result;
  }

  public function clearCache() {
    $this->cache->deleteAll();
  }
}