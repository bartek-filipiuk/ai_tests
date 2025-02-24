# Make.com Integration Module for Drupal

## INTRODUCTION

The Make.com integration module is a Drupal module that provides integration with the Make.com automation platform. It allows Drupal administrators to interact with Make.com scenarios directly from their Drupal site.

The primary use cases for this module are:

- Retrieving and managing Make.com scenarios
- Activating and deactivating scenarios
- Running scenarios with custom data
- Cloning scenarios
- Retrieving scenario execution history

## REQUIREMENTS

This module requires the following:

- Drupal 10 or 11
- PHP 8.0 or higher
- An active Make.com account with API access

## INSTALLATION

1. Install as you would normally install a contributed Drupal module. See: https://www.drupal.org/node/895232 for further information.
2. Navigate to the module configuration page and enter your Make.com API key, API URL, and Team ID.

## CONFIGURATION

1. Go to Administration » Configuration » Web services » Make.com Settings
2. Enter your Make.com API Key
3. Enter the Make.com API URL (usually https://eu1.make.com)
4. Enter your Make.com Team ID

## USAGE

The module provides a service `make_com.scenario_service` that you can use in your custom code to interact with Make.com. Here are the available methods:

- `getScenarios($limit = 10, $offset = 0)`: Get a list of scenarios
- `getAllScenarios()`: Get all scenarios (handles pagination automatically)
- `getScenarioDetails($scenarioId)`: Get details of a specific scenario
- `activateScenario($scenarioId)`: Activate a scenario
- `deactivateScenario($scenarioId)`: Deactivate a scenario
- `runScenario($scenarioId, array $data = [])`: Run a scenario with optional input data
- `cloneScenario($scenarioId, $name, $teamId)`: Clone a scenario
- `updateScenario($scenarioId, array $data)`: Update a scenario
- `deleteScenario($scenarioId)`: Delete a scenario
- `getScenarioExecutions($scenarioId, $limit = 10, $offset = 0)`: Get execution history of a scenario

## API ENDPOINTS

The module interacts with the following Make.com API endpoints:

- GET /scenarios: List scenarios
- GET /scenarios/{scenarioId}: Get scenario details
- GET /scenarios/{scenarioId}/blueprint: Get scenario blueprint
- GET /scenarios/{scenarioId}/executions: Get scenario execution history
- POST /scenarios/{scenarioId}/start: Activate a scenario
- POST /scenarios/{scenarioId}/stop: Deactivate a scenario
- POST /scenarios/{scenarioId}/run: Run a scenario
- POST /scenarios/{scenarioId}/clone: Clone a scenario
- PATCH /scenarios/{scenarioId}: Update a scenario
- DELETE /scenarios/{scenarioId}: Delete a scenario

For more detailed information about these endpoints, please refer to the official Make.com API documentation.

## MAINTAINERS

Current maintainers for Drupal 10:

- [Your Name] ([Your Drupal.org username]) - https://www.drupal.org/u/[Your Drupal.org username]

