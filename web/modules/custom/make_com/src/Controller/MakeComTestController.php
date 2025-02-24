<?php

namespace Drupal\make_com\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\make_com\Service\MakeComScenarioService;

class MakeComTestController extends ControllerBase {

  protected $scenarioService;

  public function __construct(MakeComScenarioService $scenario_service) {
    $this->scenarioService = $scenario_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('make_com.scenario_service')
    );
  }

  public function testApi() {
    $scenarios = $this->scenarioService->getScenarios();
    
    // Wyświetl wyniki w formie tabeli
    $build['table'] = [
      '#type' => 'table',
      '#header' => ['ID', 'Name', 'Status'],
      '#rows' => [],
    ];

    foreach ($scenarios['scenarios'] as $scenario) {
      $build['table']['#rows'][] = [
        $scenario['id'],
        $scenario['name'],
        $scenario['islinked'] ? 'Active' : 'Inactive',
      ];
    }

    return $build;
  }
}