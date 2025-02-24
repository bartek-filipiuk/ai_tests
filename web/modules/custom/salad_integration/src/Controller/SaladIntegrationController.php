<?php

namespace Drupal\salad_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class SaladIntegrationController extends ControllerBase {

  public function overview() {
    $links = [
      [
        'title' => $this->t('Salad API Settings'),
        'description' => $this->t('Configure Salad API settings'),
        'route' => 'salad_integration.settings',
      ],
      [
        'title' => $this->t('Create Transcription'),
        'description' => $this->t('Create a new transcription'),
        'route' => 'salad_integration.transcription',
      ],
      [
        'title' => $this->t('Transcriptions List'),
        'description' => $this->t('View all transcriptions'),
        'route' => 'salad_integration.transcription_list',
      ],
      [
        'title' => $this->t('Transcription Results'),
        'description' => $this->t('View transcription results'),
        'route' => 'salad_integration.result',
      ],
    ];

    $items = [];
    foreach ($links as $link) {
      $items[] = [
        '#type' => 'link',
        '#title' => $link['title'],
        '#url' => Url::fromRoute($link['route']),
        '#suffix' => '<div>' . $link['description'] . '</div><br>',
      ];
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#title' => $this->t('Salad Integration'),
      '#list_type' => 'ul',
      '#wrapper_attributes' => ['class' => 'salad-integration-links'],
    ];
  }
}
