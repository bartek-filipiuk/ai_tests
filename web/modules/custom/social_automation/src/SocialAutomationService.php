<?php

namespace Drupal\social_automation;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for social automation tasks.
 */
class SocialAutomationService implements SocialAutomationServiceInterface {

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
   * Constructs a new SocialAutomationService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(ClientInterface $http_client, LoggerChannelFactoryInterface $logger_factory) {
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function triggerWebhook($social_media, $datetime) {
    $webhook_url = 'https://hook.eu2.make.com/osfapvdsnvjv1cmq48bu01leva8x07ib';

    try {
      $response = $this->httpClient->request('POST', $webhook_url, [
        'json' => [
          'social_media' => $social_media,
          'datetime' => $datetime,
        ],
      ]);

      if ($response->getStatusCode() == 200) {
        return TRUE;
      }
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('social_automation')->error('Webhook trigger failed: @error', ['@error' => $e->getMessage()]);
    }

    return FALSE;
  }

}