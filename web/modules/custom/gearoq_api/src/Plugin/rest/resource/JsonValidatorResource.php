<?php

namespace Drupal\gearoq_api\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides a resource for JSON validation.
 *
 * @RestResource(
 *   id = "gearoq_api_json_validator",
 *   label = @Translation("JSON Validator Resource"),
 *   uri_paths = {
 *     "create" = "/api/validate/json"
 *   }
 * )
 */
class JsonValidatorResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new JsonValidatorResource object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('gearoq_api'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to POST requests.
   *
   * @param array $data
   *   The data containing the text to validate.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   */
  public function post(array $data) {
    if (empty($data['text'])) {
      return new ResourceResponse(['error' => 'No text provided'], 400);
    }

    $text = $data['text'];
    $json = $this->extractJson($text);

    if ($json === NULL) {
      return new ResourceResponse(['error' => 'No valid JSON found'], 400);
    }

    return new ResourceResponse($json);
  }

  /**
   * Extracts valid JSON from text.
   *
   * @param string $text
   *   The input text.
   *
   * @return mixed|null
   *   Decoded JSON data or NULL if no valid JSON found.
   */
  protected function extractJson($text) {
    // First try to parse the entire text as JSON
    $decoded = json_decode($text, TRUE);
    if (json_last_error() === JSON_ERROR_NONE) {
      return $decoded;
    }

    // Look for JSON between ```json markers
    if (preg_match('/```json\s*(.*?)\s*```/s', $text, $matches)) {
      $jsonContent = $matches[1];
      $decoded = json_decode($jsonContent, TRUE);
      if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
      }
    }

    return NULL;
  }
}
