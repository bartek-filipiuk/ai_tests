<?php

namespace Drupal\gearoq_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\gearoq_api\Plugin\rest\resource\JsonValidatorResource;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller for JSON validation endpoint.
 */
class JsonValidatorController extends ControllerBase {

  /**
   * The JSON validator resource.
   *
   * @var \Drupal\gearoq_api\Plugin\rest\resource\JsonValidatorResource
   */
  protected $resource;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * JsonValidatorController constructor.
   */
  public function __construct(JsonValidatorResource $resource, RequestStack $request_stack) {
    $this->resource = $resource;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.rest')->createInstance('gearoq_api_json_validator'),
      $container->get('request_stack')
    );
  }

  /**
   * Handles the request.
   */
  public function handle(Request $request) {
    $content = json_decode($request->getContent(), TRUE);
    
    // Sprawdź czy content jest null i ustaw domyślną tablicę
    if ($content === NULL) {
      return new JsonResponse(['error' => 'Invalid JSON in request body'], 400);
    }

    // Upewnij się, że przekazujemy tablicę z tekstem
    $data = ['text' => $content['text'] ?? ''];
    
    try {
      $response = $this->resource->post($data);
      return new JsonResponse($response->getResponseData(), $response->getStatusCode());
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => $e->getMessage()], 400);
    }
  }

  /**
   * Checks access for the endpoint.
   */
  public function access(AccountInterface $account) {
    // Get the current request
    $request = $this->requestStack->getCurrentRequest();
    
    // Check for basic auth credentials
    $auth = $request->headers->get('PHP_AUTH_USER');
    
    // Allow access if:
    // 1. Using basic auth with 'api' user, OR
    // 2. Currently logged in as administrator
    if ($auth === 'api' || in_array('administrator', $account->getRoles())) {
      return AccessResult::allowed()
        ->addCacheContexts(['user.roles'])
        ->addCacheContexts(['request_format']);
    }
    
    return AccessResult::forbidden()
      ->addCacheContexts(['user.roles'])
      ->addCacheContexts(['request_format']);
  }
}
