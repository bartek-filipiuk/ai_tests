<?php

namespace Drupal\salad_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\salad_integration\SaladApiClient;

class TranscriptionViewController extends ControllerBase {

  protected $entityTypeManager;
  protected $saladApiClient;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, SaladApiClient $salad_api_client) {
    $this->entityTypeManager = $entity_type_manager;
    $this->saladApiClient = $salad_api_client;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('salad_integration.api_client')
    );
  }

  public function viewTranscription($id) {
    $transcription = $this->entityTypeManager->getStorage('transcription')->load($id);

    if (!$transcription) {
      return ['#markup' => $this->t('Transcription not found.')];
    }

    $job_id = $transcription->get('job_id')->value;

    try {
      $result_data = $this->saladApiClient->getTranscriptionStatus($job_id);

    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Failed to fetch transcription data: @error', ['@error' => $e->getMessage()]));
      return ['#markup' => $this->t('Unable to retrieve transcription data.')];
    }

    $build = [
      '#theme' => 'transcription_view',
      '#transcription' => $transcription,
      '#result_data' => $result_data,
      '#attached' => [
        'library' => [
          'salad_integration/transcription-view',
        ],
      ],
    ];

    // Add cache contexts
    $build['#cache']['contexts'] = ['url.path', 'url.query_args'];

    return $build;
  }
}
