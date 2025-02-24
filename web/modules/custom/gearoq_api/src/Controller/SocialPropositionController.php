<?php

namespace Drupal\gearoq_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\gearoq_api\Plugin\rest\resource\SocialPropositionResource;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

class SocialPropositionController extends ControllerBase {

  protected $resource;

  public function __construct(SocialPropositionResource $resource) {
    $this->resource = $resource;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.rest')->createInstance('gearoq_api_social_proposition')
    );
  }

  public function handle(Request $request) {
    $content = json_decode($request->getContent(), TRUE);
    $response = $this->resource->post($content);
    return new JsonResponse($response->getResponseData(), $response->getStatusCode());
  }

  public function access(AccountInterface $account) {
    // Only allow access if the user is 'api' and has the correct permission
    if ($account->getAccountName() === 'api' && $account->hasPermission('create social_content_proposition content')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }
}