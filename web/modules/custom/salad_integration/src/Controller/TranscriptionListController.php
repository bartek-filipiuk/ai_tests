<?php

namespace Drupal\salad_integration\Controller;

use Drupal\Core\Controller\ControllerBase;

class TranscriptionListController extends ControllerBase {

  public function listTranscriptions() {
    $query = \Drupal::entityQuery('transcription')
      ->accessCheck(FALSE)
      ->sort('created', 'DESC')
      ->pager(20);
    $ids = $query->execute();

    $transcriptions = \Drupal::entityTypeManager()->getStorage('transcription')->loadMultiple($ids);

    $build['table'] = [
      '#type' => 'table',
      '#header' => ['Job ID', 'Created', 'Actions'],
      '#rows' => [],
    ];

    foreach ($transcriptions as $transcription) {
      $build['table']['#rows'][] = [
        $transcription->get('job_id')->value,
        \Drupal::service('date.formatter')->format($transcription->get('created')->value),
        [
          'data' => [
            '#type' => 'link',
            '#title' => $this->t('View'),
            '#url' => \Drupal\Core\Url::fromRoute('salad_integration.transcription_view', ['id' => $transcription->id()]),
          ],
        ],
      ];
    }

    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }
}
