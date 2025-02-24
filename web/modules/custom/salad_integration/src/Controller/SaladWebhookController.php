<?php

namespace Drupal\salad_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\salad_integration\SaladApiClient;

/**
 * Controller for handling Salad API webhooks.
 */
class SaladWebhookController extends ControllerBase {

  protected $logger;
  protected $saladApiClient;

  /**
   * Constructs a new SaladWebhookController object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\salad_integration\SaladApiClient $salad_api_client
   *   The Salad API client.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory, SaladApiClient $salad_api_client) {
    $this->logger = $logger_factory->get('salad_integration');
    $this->saladApiClient = $salad_api_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory'),
      $container->get('salad_integration.api_client')
    );
  }

  /**
   * Handles incoming webhooks from the Salad API.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function handleWebhook(Request $request) {
    $content = json_decode($request->getContent(), TRUE);

    // Validate the incoming JSON
    if (!$content) {
      $this->logger->error('Invalid JSON received in Salad webhook');
      return new JsonResponse(['error' => 'Invalid JSON'], 400);
    }

    $job_id = $content['id'] ?? null;
    $status = $content['status'] ?? null;

    // Validate required fields
    if (!$job_id || !$status) {
      $this->logger->error('Missing job ID or status in Salad webhook');
      return new JsonResponse(['error' => 'Missing job ID or status'], 400);
    }

    // Log the webhook data
    $this->logger->info('Received Salad webhook for job @job_id with status @status', [
      '@job_id' => $job_id,
      '@status' => $status,
    ]);

    if ($status === 'succeeded') {
      $result = $this->saladApiClient->getTranscriptionStatus($job_id);
      $this->saveTranscription($job_id, $status, $result);
    }

    return new JsonResponse(['message' => 'Webhook received successfully']);
  }

  protected function saveTranscription($job_id, $status, $result_data) {
    $transcription = \Drupal::entityTypeManager()->getStorage('transcription')->create([
      'job_id' => $job_id,
      'status' => $status,
      'result_data' => json_encode($result_data),
    ]);
    $transcription->save();
  }
}
